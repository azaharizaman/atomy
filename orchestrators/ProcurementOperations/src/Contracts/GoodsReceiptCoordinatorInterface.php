<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\GoodsReceiptResult;
use Nexus\ProcurementOperations\DTOs\RecordGoodsReceiptRequest;

/**
 * Contract for goods receipt workflow coordination.
 *
 * Handles recording of goods received against purchase orders,
 * integration with inventory, and GL accrual posting.
 */
interface GoodsReceiptCoordinatorInterface
{
    /**
     * Record goods receipt against a purchase order.
     *
     * This operation:
     * 1. Validates PO exists and is open
     * 2. Validates receipt quantities against PO
     * 3. Records receipt in Procurement package
     * 4. Updates inventory via Inventory package
     * 5. Posts GR-IR accrual journal entry
     * 6. Dispatches GoodsReceiptCreatedEvent
     * 7. If fully received, dispatches GoodsReceiptCompletedEvent
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\GoodsReceiptException
     * @throws \Nexus\ProcurementOperations\Exceptions\PurchaseOrderException
     */
    public function record(RecordGoodsReceiptRequest $request): GoodsReceiptResult;

    /**
     * Reverse a goods receipt (for corrections).
     *
     * This operation:
     * 1. Creates reversal entry in Procurement
     * 2. Reverses inventory movement
     * 3. Reverses GR-IR accrual
     * 4. Updates receipt status
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\GoodsReceiptException
     */
    public function reverse(
        string $tenantId,
        string $goodsReceiptId,
        string $reversedBy,
        string $reason
    ): GoodsReceiptResult;

    /**
     * Get outstanding quantities for a purchase order.
     *
     * @return array<string, array{
     *     lineId: string,
     *     productId: string,
     *     orderedQuantity: float,
     *     receivedQuantity: float,
     *     outstandingQuantity: float
     * }>
     */
    public function getOutstandingQuantities(string $tenantId, string $purchaseOrderId): array;
}
