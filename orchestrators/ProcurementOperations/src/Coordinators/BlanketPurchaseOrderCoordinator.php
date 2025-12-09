<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\ProcurementOperations\Contracts\ContractSpendTrackerInterface;
use Nexus\ProcurementOperations\DTOs\BlanketPOResult;
use Nexus\ProcurementOperations\DTOs\BlanketPurchaseOrderRequest;
use Nexus\ProcurementOperations\Events\BlanketPOCreatedEvent;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Procurement\Contracts\BlanketPurchaseOrderPersistInterface;
use Nexus\Sequencing\Contracts\SequencingManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for Blanket Purchase Order operations.
 *
 * Orchestrates the creation and management of long-term framework agreements
 * with vendors, enforcing spend limits and effective date ranges.
 *
 * Follows Advanced Orchestrator Pattern v1.1:
 * - Acts as traffic cop, not worker
 * - Validates via rules (inline for simplicity)
 * - Persists via atomic package interface
 * - Dispatches events for side effects
 */
final readonly class BlanketPurchaseOrderCoordinator
{
    public function __construct(
        private BlanketPurchaseOrderPersistInterface $blanketPoPersist,
        private ContractSpendTrackerInterface $spendTracker,
        private SequencingManagerInterface $sequencing,
        private EventDispatcherInterface $eventDispatcher,
        private ?AuditLogManagerInterface $auditLogger = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Create a new Blanket Purchase Order.
     *
     * @param BlanketPurchaseOrderRequest $request The request DTO
     * @return BlanketPOResult The result of the operation
     */
    public function create(BlanketPurchaseOrderRequest $request): BlanketPOResult
    {
        // 1. Validate request
        $validationErrors = $request->validate();
        if (!empty($validationErrors)) {
            $this->logger->warning('Blanket PO validation failed', [
                'errors' => $validationErrors,
                'tenant_id' => $request->tenantId,
            ]);
            return BlanketPOResult::failure(
                'Validation failed: ' . implode(', ', array_values($validationErrors))
            );
        }

        try {
            // 2. Generate blanket PO number
            $blanketPoNumber = $this->sequencing->getNext('blanket_po');

            // 3. Persist via atomic package
            $blanketPoId = $this->blanketPoPersist->create([
                'tenant_id' => $request->tenantId,
                'number' => $blanketPoNumber,
                'vendor_id' => $request->vendorId,
                'description' => $request->description,
                'max_amount_cents' => $request->maxAmountCents,
                'currency' => $request->currency,
                'effective_from' => $request->effectiveFrom,
                'effective_to' => $request->effectiveTo,
                'requester_id' => $request->requesterId,
                'category_ids' => $request->categoryIds,
                'terms' => $request->terms,
                'min_order_amount_cents' => $request->minOrderAmountCents,
                'warning_threshold_percent' => $request->warningThresholdPercent,
                'payment_terms' => $request->paymentTerms,
                'cost_center_id' => $request->costCenterId,
                'metadata' => $request->metadata,
                'status' => 'PENDING_APPROVAL',
            ]);

            $this->logger->info('Blanket PO created', [
                'blanket_po_id' => $blanketPoId,
                'blanket_po_number' => $blanketPoNumber,
                'tenant_id' => $request->tenantId,
                'vendor_id' => $request->vendorId,
                'max_amount_cents' => $request->maxAmountCents,
            ]);

            // 4. Dispatch event
            $this->eventDispatcher->dispatch(new BlanketPOCreatedEvent(
                blanketPoId: $blanketPoId,
                blanketPoNumber: $blanketPoNumber,
                tenantId: $request->tenantId,
                vendorId: $request->vendorId,
                maxAmountCents: $request->maxAmountCents,
                currency: $request->currency,
                effectiveFrom: $request->effectiveFrom,
                effectiveTo: $request->effectiveTo,
                createdBy: $request->requesterId,
            ));

            // 5. Log audit trail
            $this->auditLogger?->log(
                entityId: $blanketPoId,
                action: 'created',
                description: "Blanket PO {$blanketPoNumber} created for vendor {$request->vendorId}",
                metadata: [
                    'max_amount_cents' => $request->maxAmountCents,
                    'currency' => $request->currency,
                    'effective_from' => $request->effectiveFrom->format('Y-m-d'),
                    'effective_to' => $request->effectiveTo->format('Y-m-d'),
                ]
            );

            // 6. Return success result
            return BlanketPOResult::created(
                blanketPoId: $blanketPoId,
                blanketPoNumber: $blanketPoNumber,
                maxAmountCents: $request->maxAmountCents,
                status: 'PENDING_APPROVAL'
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to create Blanket PO', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->tenantId,
                'vendor_id' => $request->vendorId,
            ]);

            return BlanketPOResult::failure('Failed to create Blanket PO: ' . $e->getMessage());
        }
    }

    /**
     * Get current spend status for a Blanket PO.
     *
     * @param string $blanketPoId Blanket PO identifier
     * @return BlanketPOResult|null The spend status or null if not found
     */
    public function getSpendStatus(string $blanketPoId): ?BlanketPOResult
    {
        $context = $this->spendTracker->getSpendContext($blanketPoId);
        
        if ($context === null) {
            return null;
        }

        return BlanketPOResult::withSpendStatus(
            blanketPoId: $context->blanketPoId,
            blanketPoNumber: $context->blanketPoNumber,
            maxAmountCents: $context->maxAmountCents,
            currentSpendCents: $context->currentSpendCents,
            status: $context->status
        );
    }

    /**
     * Activate a Blanket PO (after approval).
     *
     * @param string $blanketPoId Blanket PO identifier
     * @param string $approvedBy User who approved
     * @return BlanketPOResult Result of the operation
     */
    public function activate(string $blanketPoId, string $approvedBy): BlanketPOResult
    {
        try {
            $this->blanketPoPersist->update($blanketPoId, [
                'status' => 'ACTIVE',
                'approved_by' => $approvedBy,
                'approved_at' => new \DateTimeImmutable(),
            ]);

            $context = $this->spendTracker->getSpendContext($blanketPoId);

            $this->auditLogger?->log(
                entityId: $blanketPoId,
                action: 'activated',
                description: "Blanket PO activated by {$approvedBy}",
            );

            return BlanketPOResult::withSpendStatus(
                blanketPoId: $blanketPoId,
                blanketPoNumber: $context?->blanketPoNumber ?? '',
                maxAmountCents: $context?->maxAmountCents ?? 0,
                currentSpendCents: 0,
                status: 'ACTIVE'
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to activate Blanket PO', [
                'blanket_po_id' => $blanketPoId,
                'error' => $e->getMessage(),
            ]);

            return BlanketPOResult::failure('Failed to activate: ' . $e->getMessage());
        }
    }

    /**
     * Close a Blanket PO.
     *
     * @param string $blanketPoId Blanket PO identifier
     * @param string $closedBy User who closed
     * @param string $reason Closure reason
     * @return BlanketPOResult Result of the operation
     */
    public function close(string $blanketPoId, string $closedBy, string $reason): BlanketPOResult
    {
        try {
            $context = $this->spendTracker->getSpendContext($blanketPoId);

            $this->blanketPoPersist->update($blanketPoId, [
                'status' => 'CLOSED',
                'closed_by' => $closedBy,
                'closed_at' => new \DateTimeImmutable(),
                'closure_reason' => $reason,
            ]);

            $this->auditLogger?->log(
                entityId: $blanketPoId,
                action: 'closed',
                description: "Blanket PO closed: {$reason}",
            );

            return BlanketPOResult::withSpendStatus(
                blanketPoId: $blanketPoId,
                blanketPoNumber: $context?->blanketPoNumber ?? '',
                maxAmountCents: $context?->maxAmountCents ?? 0,
                currentSpendCents: $context?->currentSpendCents ?? 0,
                status: 'CLOSED'
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to close Blanket PO', [
                'blanket_po_id' => $blanketPoId,
                'error' => $e->getMessage(),
            ]);

            return BlanketPOResult::failure('Failed to close: ' . $e->getMessage());
        }
    }
}
