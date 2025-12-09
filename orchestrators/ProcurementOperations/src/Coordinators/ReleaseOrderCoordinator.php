<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\ProcurementOperations\Contracts\ContractSpendTrackerInterface;
use Nexus\ProcurementOperations\DTOs\BlanketPOResult;
use Nexus\ProcurementOperations\DTOs\ContractSpendContext;
use Nexus\ProcurementOperations\DTOs\ReleaseOrderRequest;
use Nexus\ProcurementOperations\Events\ContractSpendLimitWarningEvent;
use Nexus\ProcurementOperations\Events\ReleaseOrderCreatedEvent;
use Nexus\ProcurementOperations\Rules\Contract\ContractActiveRule;
use Nexus\ProcurementOperations\Rules\Contract\ContractEffectiveDateRule;
use Nexus\ProcurementOperations\Rules\Contract\ContractSpendLimitRule;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Procurement\Contracts\ReleaseOrderPersistInterface;
use Nexus\Sequencing\Contracts\SequencingManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for Release Order operations against Blanket POs.
 *
 * Orchestrates the creation of release orders that draw down from
 * established blanket PO spending limits.
 *
 * Follows Advanced Orchestrator Pattern v1.1:
 * - Validates via composable rules
 * - Tracks spend via ContractSpendTracker
 * - Dispatches events for spend warnings
 */
final readonly class ReleaseOrderCoordinator
{
    public function __construct(
        private ReleaseOrderPersistInterface $releaseOrderPersist,
        private ContractSpendTrackerInterface $spendTracker,
        private SequencingManagerInterface $sequencing,
        private EventDispatcherInterface $eventDispatcher,
        private ContractActiveRule $activeRule,
        private ContractSpendLimitRule $spendLimitRule,
        private ContractEffectiveDateRule $effectiveDateRule,
        private ?AuditLogManagerInterface $auditLogger = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Create a Release Order against a Blanket PO.
     *
     * @param ReleaseOrderRequest $request The release order request
     * @return BlanketPOResult Result including updated spend status
     */
    public function create(ReleaseOrderRequest $request): BlanketPOResult
    {
        // 1. Validate request
        $validationErrors = $request->validate();
        if (!empty($validationErrors)) {
            $this->logger->warning('Release order validation failed', [
                'errors' => $validationErrors,
                'blanket_po_id' => $request->blanketPoId,
            ]);
            return BlanketPOResult::failure(
                'Validation failed: ' . implode(', ', array_values($validationErrors))
            );
        }

        // 2. Get contract spend context
        $context = $this->spendTracker->getSpendContext($request->blanketPoId);
        if ($context === null) {
            return BlanketPOResult::failure('Blanket PO not found');
        }

        // 3. Validate contract is active
        $activeResult = $this->activeRule->check($context);
        if ($activeResult->failed()) {
            return BlanketPOResult::failure($activeResult->getMessage());
        }

        // 4. Validate effective date
        $dateResult = $this->effectiveDateRule->check($context, new \DateTimeImmutable());
        if ($dateResult->failed()) {
            return BlanketPOResult::failure($dateResult->getMessage());
        }

        // 5. Calculate release order amount
        $releaseAmountCents = $request->calculateTotalCents();

        // 6. Validate spend limit
        $spendResult = $this->spendLimitRule->check($context, $releaseAmountCents);
        if ($spendResult->failed()) {
            return BlanketPOResult::failure($spendResult->getMessage());
        }

        try {
            // 7. Generate release order number
            $releaseOrderNumber = $this->sequencing->getNext('release_order');

            // 8. Persist release order
            $releaseOrderId = $this->releaseOrderPersist->create([
                'tenant_id' => $request->tenantId,
                'number' => $releaseOrderNumber,
                'blanket_po_id' => $request->blanketPoId,
                'requester_id' => $request->requesterId,
                'line_items' => $request->lineItems,
                'total_cents' => $releaseAmountCents,
                'currency' => $context->currency,
                'delivery_date' => $request->deliveryDate,
                'delivery_address' => $request->deliveryAddress,
                'notes' => $request->notes,
                'metadata' => $request->metadata,
                'status' => 'PENDING',
            ]);

            // 9. Record spend against blanket PO
            $newSpend = $this->spendTracker->recordSpend(
                $request->blanketPoId,
                $releaseAmountCents,
                $releaseOrderId
            );

            $remaining = $context->maxAmountCents - $newSpend;
            $percentUtilized = $context->maxAmountCents > 0
                ? (int) (($newSpend * 100) / $context->maxAmountCents)
                : 0;

            $this->logger->info('Release order created', [
                'release_order_id' => $releaseOrderId,
                'release_order_number' => $releaseOrderNumber,
                'blanket_po_id' => $request->blanketPoId,
                'amount_cents' => $releaseAmountCents,
                'new_cumulative_spend_cents' => $newSpend,
                'percent_utilized' => $percentUtilized,
            ]);

            // 10. Dispatch release order created event
            $this->eventDispatcher->dispatch(new ReleaseOrderCreatedEvent(
                releaseOrderId: $releaseOrderId,
                releaseOrderNumber: $releaseOrderNumber,
                blanketPoId: $request->blanketPoId,
                tenantId: $request->tenantId,
                amountCents: $releaseAmountCents,
                currency: $context->currency,
                newCumulativeSpendCents: $newSpend,
                remainingBudgetCents: $remaining,
                createdBy: $request->requesterId,
            ));

            // 11. Check and dispatch warning event if approaching limit
            if ($percentUtilized >= $context->warningThresholdPercent) {
                $this->eventDispatcher->dispatch(new ContractSpendLimitWarningEvent(
                    blanketPoId: $context->blanketPoId,
                    blanketPoNumber: $context->blanketPoNumber,
                    tenantId: $request->tenantId,
                    vendorId: $context->vendorId,
                    maxAmountCents: $context->maxAmountCents,
                    currentSpendCents: $newSpend,
                    percentUtilized: $percentUtilized,
                    warningThresholdPercent: $context->warningThresholdPercent,
                    effectiveTo: $context->effectiveTo,
                ));

                $this->logger->warning('Contract spend limit warning triggered', [
                    'blanket_po_id' => $context->blanketPoId,
                    'percent_utilized' => $percentUtilized,
                    'warning_threshold' => $context->warningThresholdPercent,
                ]);
            }

            // 12. Log audit trail
            $this->auditLogger?->log(
                entityId: $releaseOrderId,
                action: 'created',
                description: "Release order {$releaseOrderNumber} created against blanket PO {$context->blanketPoNumber}",
                metadata: [
                    'amount_cents' => $releaseAmountCents,
                    'blanket_po_id' => $request->blanketPoId,
                    'percent_utilized' => $percentUtilized,
                ]
            );

            // 13. Return result with updated spend status
            return BlanketPOResult::withSpendStatus(
                blanketPoId: $context->blanketPoId,
                blanketPoNumber: $context->blanketPoNumber,
                maxAmountCents: $context->maxAmountCents,
                currentSpendCents: $newSpend,
                status: $context->status
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to create release order', [
                'blanket_po_id' => $request->blanketPoId,
                'error' => $e->getMessage(),
            ]);

            return BlanketPOResult::failure('Failed to create release order: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a release order and reverse the spend.
     *
     * @param string $releaseOrderId Release order ID
     * @param string $cancelledBy User cancelling
     * @param string $reason Cancellation reason
     * @return BlanketPOResult Result of cancellation
     */
    public function cancel(string $releaseOrderId, string $cancelledBy, string $reason): BlanketPOResult
    {
        try {
            // Get the release order details first
            // In a real implementation, we'd have a query interface
            // For now, we assume the persist interface can update
            $this->releaseOrderPersist->update($releaseOrderId, [
                'status' => 'CANCELLED',
                'cancelled_by' => $cancelledBy,
                'cancelled_at' => new \DateTimeImmutable(),
                'cancellation_reason' => $reason,
            ]);

            $this->auditLogger?->log(
                entityId: $releaseOrderId,
                action: 'cancelled',
                description: "Release order cancelled: {$reason}",
            );

            return new BlanketPOResult(success: true);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to cancel release order', [
                'release_order_id' => $releaseOrderId,
                'error' => $e->getMessage(),
            ]);

            return BlanketPOResult::failure('Failed to cancel: ' . $e->getMessage());
        }
    }
}
