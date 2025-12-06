<?php

declare(strict_types=1);

namespace Nexus\Procurement\Events;

/**
 * Dispatched when all items on a purchase order have been received (PO fully received).
 *
 * Consuming applications or orchestrators may listen to this event
 * to trigger side-effects such as:
 * - PO status update to "Fully Received"
 * - Final budget release
 * - Close requisition (if applicable)
 * - Audit logging
 */
final readonly class GoodsReceiptCompletedEvent
{
    /**
     * @param string $purchaseOrderId Purchase order that is now fully received
     * @param string $tenantId Tenant context
     * @param string $purchaseOrderNumber Human-readable PO number
     * @param string $vendorId Vendor party ID
     * @param array<string> $goodsReceiptIds All GRN IDs that fulfilled this PO
     * @param float $totalOrderedQuantity Total quantity ordered across all lines
     * @param float $totalReceivedQuantity Total quantity received across all GRNs
     * @param int $totalOrderedAmountCents Total PO amount in cents
     * @param int $totalReceivedAmountCents Total received value in cents
     * @param string $currency Currency code (ISO 4217)
     * @param \DateTimeImmutable $completedAt Timestamp when fully received
     */
    public function __construct(
        public readonly string $purchaseOrderId,
        public readonly string $tenantId,
        public readonly string $purchaseOrderNumber,
        public readonly string $vendorId,
        public readonly array $goodsReceiptIds,
        public readonly float $totalOrderedQuantity,
        public readonly float $totalReceivedQuantity,
        public readonly int $totalOrderedAmountCents,
        public readonly int $totalReceivedAmountCents,
        public readonly string $currency,
        public readonly \DateTimeImmutable $completedAt,
    ) {}
}
