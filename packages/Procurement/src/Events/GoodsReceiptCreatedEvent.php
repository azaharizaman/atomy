<?php

declare(strict_types=1);

namespace Nexus\Procurement\Events;

/**
 * Dispatched when a goods receipt note (GRN) is created.
 *
 * Consuming applications or orchestrators may listen to this event
 * to trigger side-effects such as:
 * - Inventory stock update
 * - GL accrual posting (GR-IR)
 * - Budget commitment release
 * - Trigger 3-way matching readiness check
 * - Audit logging
 */
final readonly class GoodsReceiptCreatedEvent
{
    /**
     * @param string $goodsReceiptId Unique identifier of the goods receipt
     * @param string $tenantId Tenant context
     * @param string $goodsReceiptNumber Human-readable GRN number
     * @param string $purchaseOrderId Associated purchase order ID
     * @param string $purchaseOrderNumber Associated PO number
     * @param string $vendorId Vendor party ID
     * @param string $warehouseId Receiving warehouse ID
     * @param string $receivedBy User ID who received the goods
     * @param array<int, array{
     *     lineId: string,
     *     poLineId: string,
     *     productId: string,
     *     description: string,
     *     quantityReceived: float,
     *     quantityOrdered: float,
     *     unitOfMeasure: string,
     *     unitCostCents: int,
     *     currency: string,
     *     lotNumber: string|null,
     *     serialNumbers: array<string>,
     *     expiryDate: string|null,
     *     binLocation: string|null
     * }> $lineItems Received line items
     * @param int $totalValueCents Total value of received goods in cents
     * @param string $currency Currency code (ISO 4217)
     * @param bool $isPartialReceipt Whether this is a partial receipt
     * @param \DateTimeImmutable $receivedAt Timestamp of receipt
     */
    public function __construct(
        public readonly string $goodsReceiptId,
        public readonly string $tenantId,
        public readonly string $goodsReceiptNumber,
        public readonly string $purchaseOrderId,
        public readonly string $purchaseOrderNumber,
        public readonly string $vendorId,
        public readonly string $warehouseId,
        public readonly string $receivedBy,
        public readonly array $lineItems,
        public readonly int $totalValueCents,
        public readonly string $currency,
        public readonly bool $isPartialReceipt,
        public readonly \DateTimeImmutable $receivedAt,
    ) {}
}
