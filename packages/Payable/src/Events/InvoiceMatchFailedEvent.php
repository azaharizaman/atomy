<?php

declare(strict_types=1);

namespace Nexus\Payable\Events;

/**
 * Dispatched when a vendor invoice fails 3-way matching due to variance.
 *
 * Consuming applications or orchestrators may listen to this event
 * to trigger side-effects such as:
 * - Route for variance review/approval
 * - Notify AP manager
 * - Create exception task
 * - Audit logging
 */
final readonly class InvoiceMatchFailedEvent
{
    /**
     * @param string $vendorBillId Unique identifier of the vendor bill
     * @param string $tenantId Tenant context
     * @param string $vendorBillNumber System-generated bill number
     * @param string $purchaseOrderId Associated purchase order ID
     * @param string $purchaseOrderNumber Associated PO number
     * @param array<string> $goodsReceiptIds GRN IDs used in matching attempt
     * @param string $vendorId Vendor party ID
     * @param int $invoiceAmountCents Invoice total in cents
     * @param int $poAmountCents PO total in cents
     * @param int $receivedAmountCents Total received value in cents
     * @param string $currency Currency code (ISO 4217)
     * @param float $priceVariancePercent Price variance percentage
     * @param float $quantityVariancePercent Quantity variance percentage
     * @param float $priceTolerancePercent Configured price tolerance
     * @param float $quantityTolerancePercent Configured quantity tolerance
     * @param array<string, array{
     *     type: string,
     *     field: string,
     *     expected: mixed,
     *     actual: mixed,
     *     variancePercent: float
     * }> $varianceDetails Detailed breakdown of variances
     * @param string $failureReason Human-readable failure reason
     * @param \DateTimeImmutable $failedAt Timestamp of match failure
     */
    public function __construct(
        public readonly string $vendorBillId,
        public readonly string $tenantId,
        public readonly string $vendorBillNumber,
        public readonly string $purchaseOrderId,
        public readonly string $purchaseOrderNumber,
        public readonly array $goodsReceiptIds,
        public readonly string $vendorId,
        public readonly int $invoiceAmountCents,
        public readonly int $poAmountCents,
        public readonly int $receivedAmountCents,
        public readonly string $currency,
        public readonly float $priceVariancePercent,
        public readonly float $quantityVariancePercent,
        public readonly float $priceTolerancePercent,
        public readonly float $quantityTolerancePercent,
        public readonly array $varianceDetails,
        public readonly string $failureReason,
        public readonly \DateTimeImmutable $failedAt,
    ) {}
}
