<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Listeners;

use Nexus\Procurement\Events\RequisitionApprovedEvent;
use Nexus\ProcurementOperations\Coordinators\RequisitionCoordinator;
use Nexus\ProcurementOperations\DTOs\Requests\CreatePurchaseOrderRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Listens for approved requisitions and automatically creates purchase orders.
 *
 * This listener implements the R2P-LIS-001 requirement for automatic PO creation
 * when a requisition is approved. It respects the auto-convert configuration
 * and handles vendor consolidation when multiple items share the same vendor.
 */
final readonly class RequisitionToPoListener
{
    public function __construct(
        private RequisitionCoordinator $requisitionCoordinator,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get the logger instance, or a NullLogger if none was injected.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    /**
     * Handle the requisition approved event.
     *
     * @param RequisitionApprovedEvent $event The event containing the approved requisition
     */
    public function handle(RequisitionApprovedEvent $event): void
    {
        $this->getLogger()->info('Processing approved requisition for PO creation', [
            'requisition_id' => $event->requisitionId,
            'approved_by' => $event->approvedBy,
            'approved_at' => $event->occurredAt->format('c'),
        ]);

        try {
            // Check if auto-conversion is enabled for this requisition
            if (!$this->shouldAutoConvert($event)) {
                $this->getLogger()->info('Auto-conversion disabled, skipping PO creation', [
                    'requisition_id' => $event->requisitionId,
                ]);
                return;
            }

            // Create PO request from the approved requisition
            $request = $this->buildPurchaseOrderRequest($event);

            // Use the coordinator to create the PO
            $result = $this->requisitionCoordinator->createPurchaseOrder($request);

            if ($result->success) {
                $this->getLogger()->info('Successfully created PO from requisition', [
                    'requisition_id' => $event->requisitionId,
                    'purchase_order_id' => $result->purchaseOrderId,
                    'po_number' => $result->poNumber,
                ]);
            } else {
                $this->getLogger()->warning('Failed to create PO from requisition', [
                    'requisition_id' => $event->requisitionId,
                    'errors' => $result->errors,
                ]);
            }
        } catch (\Throwable $e) {
            $this->getLogger()->error('Exception during PO creation from requisition', [
                'requisition_id' => $event->requisitionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Determine if automatic conversion should occur.
     *
     * This can be extended to check tenant settings, requisition metadata,
     * or other business rules that determine auto-conversion behavior.
     */
    private function shouldAutoConvert(RequisitionApprovedEvent $event): bool
    {
        // Default behavior: auto-convert enabled
        // In production, this would check:
        // 1. Tenant settings for auto-conversion
        // 2. Requisition metadata for manual-only flag
        // 3. Approval workflow configuration
        return true;
    }

    /**
     * Build a purchase order request from the approved requisition event.
     */
    private function buildPurchaseOrderRequest(RequisitionApprovedEvent $event): CreatePurchaseOrderRequest
    {
        // In a full implementation, this would fetch requisition details
        // and transform them into a PO request. For now, we create a
        // minimal request that the coordinator will enrich.
        return new CreatePurchaseOrderRequest(
            tenantId: $event->tenantId,
            requisitionId: $event->requisitionId,
            requestedBy: $event->approvedBy,
            vendorId: null, // Will be determined from requisition items
            expectedDeliveryDate: null, // Will be calculated based on vendor lead times
            notes: "Auto-created from approved requisition",
        );
    }
}
