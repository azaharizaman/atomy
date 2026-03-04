<?php

declare(strict_types=1);

namespace Nexus\Budget\Listeners;

use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Budget\Contracts\BudgetTransactionQueryInterface;
use Nexus\Budget\Contracts\PurchaseOrderApprovedEventInterface;
use Nexus\Budget\Contracts\PurchaseOrderCancelledEventInterface;
use Nexus\Budget\Contracts\PurchaseOrderClosedEventInterface;
use Nexus\Budget\Enums\TransactionType;
use Psr\Log\LoggerInterface;

/**
 * Procurement Event Listener
 * 
 * Listens to Procurement package events to manage budget commitments.
 * - PO Approved: Commit budget for PO total
 * - PO Cancelled: Release committed budget
 * - PO Closed: Release any remaining commitments
 */
final readonly class ProcurementEventListener
{
    public function __construct(
        private BudgetManagerInterface $budgetManager,
        private BudgetRepositoryInterface $budgetRepository,
        private BudgetTransactionQueryInterface $budgetTransactionQuery,
        private LoggerInterface $logger
    ) {}

    /**
     * Handle PO approved event - commit budget
     */
    public function onPurchaseOrderApproved(PurchaseOrderApprovedEventInterface $event): void
    {
        try {
            // Find budget for the PO's cost center/department
            $budgetId = $this->resolveBudgetId($event);
            if (!$budgetId) {
                $this->logger->warning('No budget found for PO', [
                    'po_id' => $event->getPurchaseOrderId(),
                ]);
                return;
            }

            // Commit the PO total amount
            $accountId = $this->resolveAccountId($event);
            $this->budgetManager->commitAmount(
                budgetId: $budgetId,
                amount: $event->getTotalAmount(),
                accountId: $accountId,
                sourceType: 'purchase_order',
                sourceId: $event->getPurchaseOrderId(),
                sourceLineNumber: 0
            );

            $this->logger->info('Budget committed for PO', [
                'po_id' => $event->getPurchaseOrderId(),
                'budget_id' => $budgetId,
                'amount' => (string) $event->getTotalAmount(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to commit budget for PO', [
                'po_id' => $event->getPurchaseOrderId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle PO cancelled event - release commitment
     */
    public function onPurchaseOrderCancelled(PurchaseOrderCancelledEventInterface $event): void
    {
        try {
            $releaseCount = $this->releaseCommitmentsForPurchaseOrder($event->getPurchaseOrderId(), 'po_cancelled');

            $this->logger->info('Budget commitment released for cancelled PO', [
                'po_id' => $event->getPurchaseOrderId(),
                'released_count' => $releaseCount,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to release commitment for cancelled PO', [
                'po_id' => $event->getPurchaseOrderId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle PO closed event - release any remaining commitments
     */
    public function onPurchaseOrderClosed(PurchaseOrderClosedEventInterface $event): void
    {
        try {
            $releaseCount = $this->releaseCommitmentsForPurchaseOrder($event->getPurchaseOrderId(), 'po_closed');

            $this->logger->info('Budget commitment released for closed PO', [
                'po_id' => $event->getPurchaseOrderId(),
                'released_count' => $releaseCount,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to release commitment for closed PO', [
                'po_id' => $event->getPurchaseOrderId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve budget ID from PO event
     * 
     * This would query the PO to get department/cost center
     * and find the appropriate budget.
     */
    private function resolveBudgetId(PurchaseOrderApprovedEventInterface $event): ?string
    {
        // Intentionally touch event in placeholder logic until real PO mapping is implemented.
        $placeholderPurchaseOrderId = $event->getPurchaseOrderId();
        unset($placeholderPurchaseOrderId);

        // TODO: Query PO for department/cost_center and resolve active budget for current period.
        // In real scenario, would:
        // 1. Query PO to get department_id or cost_center_id
        // 2. Query budgets table to find active budget for that department in current period
        // 3. Return budget_id
        
        // For now, return null to indicate no mapping found
        return null;
    }

    private function resolveAccountId(PurchaseOrderApprovedEventInterface $event): string
    {
        $directAccountId = trim((string) ($event->getAccountId() ?? ''));
        if ($directAccountId !== '') {
            return $directAccountId;
        }

        if (method_exists($event, 'getPurchaseOrder')) {
            $purchaseOrder = $event->getPurchaseOrder();
            if (is_object($purchaseOrder) && method_exists($purchaseOrder, 'getAccountId')) {
                $resolvedAccountId = trim((string) ($purchaseOrder->getAccountId() ?? ''));
                if ($resolvedAccountId !== '') {
                    return $resolvedAccountId;
                }
            }
        }

        throw new \RuntimeException(sprintf('Unable to resolve account ID for purchase order %s.', $event->getPurchaseOrderId()));
    }

    private function releaseCommitmentsForPurchaseOrder(string $purchaseOrderId, string $reason): int
    {
        $transactions = $this->budgetTransactionQuery->findBySource('purchase_order', $purchaseOrderId);
        $releaseCount = 0;

        foreach ($transactions as $transaction) {
            if ($transaction->getTransactionType() !== TransactionType::Commitment) {
                continue;
            }

            $this->budgetManager->releaseCommitment(
                budgetId: $transaction->getBudgetId(),
                amount: $transaction->getAmount(),
                sourceType: 'purchase_order',
                sourceId: $purchaseOrderId,
                sourceLineNumber: $transaction->getSourceLineNumber() ?? 0
            );
            $releaseCount++;
        }

        $this->logger->info('Budget commitment release routine executed for purchase order.', [
            'po_id' => $purchaseOrderId,
            'release_reason' => $reason,
            'released_count' => $releaseCount,
        ]);

        return $releaseCount;
    }
}
