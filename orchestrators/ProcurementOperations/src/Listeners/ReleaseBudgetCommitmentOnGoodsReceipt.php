<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Listeners;

use Nexus\Budget\Contracts\BudgetCommitmentManagerInterface;
use Nexus\Common\ValueObjects\Money;
use Nexus\Procurement\Events\GoodsReceiptCompletedEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Releases budget commitments when goods receipt is completed.
 *
 * This listener handles:
 * - Release of encumbered budget amounts
 * - Recording actual expenditure
 * - Budget variance tracking
 *
 * Budget Flow:
 * 1. When PO is created: Budget is encumbered (committed)
 * 2. When GR is completed: Budget commitment is released
 * 3. Actual expenditure is recorded against budget
 *
 * The difference between encumbered amount and actual received value
 * is tracked as budget variance for reporting.
 */
final readonly class ReleaseBudgetCommitmentOnGoodsReceipt
{
    public function __construct(
        private ?BudgetCommitmentManagerInterface $budgetManager = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Handle the goods receipt completed event.
     *
     * This is triggered when all items on a PO have been received,
     * allowing full release of the budget commitment.
     */
    public function handle(GoodsReceiptCompletedEvent $event): void
    {
        // Budget management is optional - skip if not configured
        if ($this->budgetManager === null) {
            $this->logger->debug('Budget management not configured, skipping commitment release', [
                'purchase_order_id' => $event->purchaseOrderId,
            ]);
            return;
        }

        $this->logger->info('Processing budget commitment release for completed goods receipt', [
            'purchase_order_id' => $event->purchaseOrderId,
            'purchase_order_number' => $event->purchaseOrderNumber,
            'total_ordered_cents' => $event->totalOrderedAmountCents,
            'total_received_cents' => $event->totalReceivedAmountCents,
            'currency' => $event->currency,
        ]);

        try {
            // Calculate amounts
            $encumberedAmount = Money::fromCents($event->totalOrderedAmountCents, $event->currency);
            $actualAmount = Money::fromCents($event->totalReceivedAmountCents, $event->currency);

            // Release the encumbered budget commitment
            $this->budgetManager->releaseCommitment(
                referenceType: 'purchase_order',
                referenceId: $event->purchaseOrderId,
                encumberedAmount: $encumberedAmount,
            );

            $this->logger->debug('Released budget encumbrance', [
                'purchase_order_id' => $event->purchaseOrderId,
                'encumbered_cents' => $event->totalOrderedAmountCents,
            ]);

            // Record actual expenditure
            $this->budgetManager->recordActualExpenditure(
                referenceType: 'purchase_order',
                referenceId: $event->purchaseOrderId,
                amount: $actualAmount,
                description: sprintf(
                    'Goods received for PO %s',
                    $event->purchaseOrderNumber,
                ),
            );

            $this->logger->debug('Recorded actual expenditure', [
                'purchase_order_id' => $event->purchaseOrderId,
                'actual_cents' => $event->totalReceivedAmountCents,
            ]);

            // Track variance if amounts differ
            $varianceCents = $event->totalOrderedAmountCents - $event->totalReceivedAmountCents;
            if ($varianceCents !== 0) {
                $this->trackBudgetVariance($event, $varianceCents);
            }

            $this->logger->info('Successfully processed budget commitment release', [
                'purchase_order_id' => $event->purchaseOrderId,
                'encumbered_cents' => $event->totalOrderedAmountCents,
                'actual_cents' => $event->totalReceivedAmountCents,
                'variance_cents' => $varianceCents,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to release budget commitment', [
                'purchase_order_id' => $event->purchaseOrderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't re-throw - budget release failure shouldn't block goods receipt
            // The variance can be reconciled manually
        }
    }

    /**
     * Track budget variance between ordered and received amounts.
     *
     * Positive variance = under-received (favorable)
     * Negative variance = over-received (unfavorable)
     */
    private function trackBudgetVariance(GoodsReceiptCompletedEvent $event, int $varianceCents): void
    {
        if ($this->budgetManager === null) {
            return;
        }

        $varianceType = $varianceCents > 0 ? 'favorable' : 'unfavorable';
        $varianceAmount = Money::fromCents(abs($varianceCents), $event->currency);

        $this->budgetManager->recordVariance(
            referenceType: 'purchase_order',
            referenceId: $event->purchaseOrderId,
            varianceType: $varianceType,
            amount: $varianceAmount,
            description: sprintf(
                '%s variance on PO %s: ordered %d, received %d (%s)',
                ucfirst($varianceType),
                $event->purchaseOrderNumber,
                $event->totalOrderedAmountCents,
                $event->totalReceivedAmountCents,
                $event->currency,
            ),
        );

        $this->logger->info('Recorded budget variance', [
            'purchase_order_id' => $event->purchaseOrderId,
            'variance_type' => $varianceType,
            'variance_cents' => $varianceCents,
        ]);
    }
}
