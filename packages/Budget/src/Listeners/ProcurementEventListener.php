<?php

declare(strict_types=1);

namespace Nexus\Budget\Listeners;

use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Budget\Contracts\BudgetQueryInterface;
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
    private const string RELEASED_KEY = 'released';
    private const string RELEASED_AT_KEY = 'released_at';
    private const string RELEASE_TX_ID_KEY = 'release_transaction_id';

    public function __construct(
        private BudgetManagerInterface $budgetManager,
        private BudgetRepositoryInterface $budgetRepository,
        private BudgetQueryInterface $budgetQuery,
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
        $purchaseOrder = method_exists($event, 'getPurchaseOrder') ? $event->getPurchaseOrder() : null;
        $periodId = $this->readStringProperty($event, 'getPeriodId')
            ?? (is_object($purchaseOrder) ? $this->readStringProperty($purchaseOrder, 'getPeriodId') : null);

        if ($periodId !== null) {
            $departmentId = $this->readStringProperty($event, 'getDepartmentId')
                ?? (is_object($purchaseOrder) ? $this->readStringProperty($purchaseOrder, 'getDepartmentId') : null);
            if ($departmentId !== null) {
                $budget = $this->budgetRepository->findByDepartment($departmentId, $periodId);
                if ($budget !== null) {
                    return $budget->getId();
                }
            }

            try {
                $accountId = $this->resolveAccountId($event);
                $budget = $this->budgetRepository->findByAccountAndPeriod($accountId, $periodId);
                if ($budget !== null) {
                    return $budget->getId();
                }
            } catch (\Throwable $e) {
                $this->logger->debug('Account-based budget resolution failed before falling back to alternate lookup strategies.', [
                    'event' => get_class($event),
                    'period_id' => $periodId,
                    'error' => $e->getMessage(),
                ]);
            }

            $costCenterId = $this->readStringProperty($event, 'getCostCenterId')
                ?? (is_object($purchaseOrder) ? $this->readStringProperty($purchaseOrder, 'getCostCenterId') : null);
            if ($costCenterId !== null) {
                $budget = $this->budgetQuery->findByCostCenterAndPeriod($costCenterId, $periodId);
                if ($budget !== null) {
                    return $budget->getId();
                }
            }
        }

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
            if ($this->isReleasedTransaction($transaction)) {
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

    private function readStringProperty(object $source, string $method): ?string
    {
        if (!method_exists($source, $method)) {
            return null;
        }

        $value = $source->{$method}();
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value === '' ? null : $value;
    }

    /**
     * Check if a transaction has already been released
     * 
     * Relies on the following metadata keys:
     * - 'released' (bool): Explicit release flag
     * - 'released_at' (string): ISO 8601 timestamp of release
     * - 'release_transaction_id' (string): ID of the transaction that performed the release
     */
    private function isReleasedTransaction(object $transaction): bool
    {
        if (!method_exists($transaction, 'getMetadata')) {
            return false;
        }

        $metadata = $transaction->getMetadata();
        if (!is_array($metadata)) {
            return false;
        }

        return ($metadata[self::RELEASED_KEY] ?? false) === true ||
            (is_string($metadata[self::RELEASED_AT_KEY] ?? null) && trim($metadata[self::RELEASED_AT_KEY]) !== '') ||
            (isset($metadata[self::RELEASE_TX_ID_KEY]) && (string) $metadata[self::RELEASE_TX_ID_KEY] !== '');
    }
}
