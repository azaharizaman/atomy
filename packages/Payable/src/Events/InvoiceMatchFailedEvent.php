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
        public string $vendorBillId,
        public string $tenantId,
        public string $vendorBillNumber,
        public string $purchaseOrderId,
        public string $purchaseOrderNumber,
        public array $goodsReceiptIds,
        public string $vendorId,
        public int $invoiceAmountCents,
        public int $poAmountCents,
        public int $receivedAmountCents,
        public string $currency,
        public float $priceVariancePercent,
        public float $quantityVariancePercent,
        public float $priceTolerancePercent,
        public float $quantityTolerancePercent,
        public array $varianceDetails,
        public string $failureReason,
        public \DateTimeImmutable $failedAt,
    ) {}
}
