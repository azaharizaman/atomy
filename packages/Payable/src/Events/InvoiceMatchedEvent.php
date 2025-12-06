<?php

declare(strict_types=1);

namespace Nexus\Payable\Events;

/**
 * Dispatched when a vendor invoice is successfully matched (3-way match passed).
 *
 * Consuming applications or orchestrators may listen to this event
 * to trigger side-effects such as:
 * - Reverse GR-IR accrual
 * - Post AP liability journal entry
 * - Update invoice status
 * - Audit logging
 */
final readonly class InvoiceMatchedEvent
{
    /**
     * @param string $vendorBillId Unique identifier of the vendor bill
     * @param string $tenantId Tenant context
     * @param string $vendorBillNumber System-generated bill number
     * @param string $purchaseOrderId Matched purchase order ID
     * @param string $purchaseOrderNumber Matched PO number
     * @param array<string> $matchedGoodsReceiptIds GRN IDs matched to this invoice
     * @param string $vendorId Vendor party ID
     * @param int $invoiceAmountCents Invoice total in cents
     * @param int $poAmountCents PO total in cents
     * @param int $receivedAmountCents Total received value in cents
     * @param string $currency Currency code (ISO 4217)
     * @param float $priceVariancePercent Price variance percentage
     * @param float $quantityVariancePercent Quantity variance percentage
     * @param bool $withinTolerance Whether variances are within tolerance
     * @param string $matchedBy User ID who performed the match (or 'system' for auto-match)
     * @param \DateTimeImmutable $matchedAt Timestamp of match
     */
    public function __construct(
        public readonly string $vendorBillId,
        public readonly string $tenantId,
        public readonly string $vendorBillNumber,
        public readonly string $purchaseOrderId,
        public readonly string $purchaseOrderNumber,
        public readonly array $matchedGoodsReceiptIds,
        public readonly string $vendorId,
        public readonly int $invoiceAmountCents,
        public readonly int $poAmountCents,
        public readonly int $receivedAmountCents,
        public readonly string $currency,
        public readonly float $priceVariancePercent,
        public readonly float $quantityVariancePercent,
        public readonly bool $withinTolerance,
        public readonly string $matchedBy,
        public readonly \DateTimeImmutable $matchedAt,
    ) {}
}
