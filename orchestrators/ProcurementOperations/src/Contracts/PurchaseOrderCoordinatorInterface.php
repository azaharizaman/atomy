<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\CreatePurchaseOrderRequest;
use Nexus\ProcurementOperations\DTOs\PurchaseOrderResult;

/**
 * Contract for purchase order workflow coordination.
 *
 * Handles PO creation from requisitions, vendor transmission, amendments,
 * and lifecycle management.
 */
interface PurchaseOrderCoordinatorInterface
{
    /**
     * Create a purchase order from an approved requisition.
     *
     * This operation:
     * 1. Validates requisition is approved
     * 2. Validates vendor is active
     * 3. Creates PO in Procurement package
     * 4. Links to budget commitment
     * 5. Dispatches PurchaseOrderCreatedEvent
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\PurchaseOrderException
     * @throws \Nexus\ProcurementOperations\Exceptions\RequisitionException
     */
    public function createFromRequisition(CreatePurchaseOrderRequest $request): PurchaseOrderResult;

    /**
     * Send purchase order to vendor.
     *
     * This operation:
     * 1. Validates PO is ready to send
     * 2. Transmits via configured channel (email, EDI, portal)
     * 3. Updates PO status to Sent
     * 4. Dispatches PurchaseOrderSentEvent
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\PurchaseOrderException
     */
    public function sendToVendor(
        string $tenantId,
        string $purchaseOrderId,
        string $sentBy,
        string $deliveryMethod = 'email',
        ?string $deliveryAddress = null
    ): PurchaseOrderResult;

    /**
     * Amend an existing purchase order.
     *
     * This operation:
     * 1. Validates PO can be amended
     * 2. Calculates variance from original
     * 3. Adjusts budget commitment if needed
     * 4. Creates amendment record
     * 5. Dispatches PurchaseOrderAmendedEvent
     *
     * @param array<string, mixed> $amendments Changes to apply
     * @throws \Nexus\ProcurementOperations\Exceptions\PurchaseOrderException
     */
    public function amend(
        string $tenantId,
        string $purchaseOrderId,
        array $amendments,
        string $amendedBy,
        string $reason
    ): PurchaseOrderResult;

    /**
     * Cancel a purchase order.
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\PurchaseOrderException
     */
    public function cancel(
        string $tenantId,
        string $purchaseOrderId,
        string $cancelledBy,
        string $reason
    ): PurchaseOrderResult;

    /**
     * Close a purchase order (all goods received or manually closed).
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\PurchaseOrderException
     */
    public function close(
        string $tenantId,
        string $purchaseOrderId,
        string $closedBy,
        ?string $reason = null
    ): PurchaseOrderResult;
}
