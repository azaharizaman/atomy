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
        private string $vendorBillId,
        private string $tenantId,
        private string $vendorBillNumber,
        private string $purchaseOrderId,
        private string $purchaseOrderNumber,
        private array $goodsReceiptIds,
        private string $vendorId,
        private int $invoiceAmountCents,
        private int $poAmountCents,
        private int $receivedAmountCents,
        private string $currency,
        private float $priceVariancePercent,
        private float $quantityVariancePercent,
        private float $priceTolerancePercent,
        private float $quantityTolerancePercent,
        private array $varianceDetails,
        private string $failureReason,
        private \DateTimeImmutable $failedAt,
    ) {}

    public function getVendorBillId(): string
    {
        return $this->vendorBillId;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getVendorBillNumber(): string
    {
        return $this->vendorBillNumber;
    }

    public function getPurchaseOrderId(): string
    {
        return $this->purchaseOrderId;
    }

    public function getPurchaseOrderNumber(): string
    {
        return $this->purchaseOrderNumber;
    }

    /**
     * @return array<string>
     */
    public function getGoodsReceiptIds(): array
    {
        return $this->goodsReceiptIds;
    }

    public function getVendorId(): string
    {
        return $this->vendorId;
    }

    public function getInvoiceAmountCents(): int
    {
        return $this->invoiceAmountCents;
    }

    public function getPoAmountCents(): int
    {
        return $this->poAmountCents;
    }

    public function getReceivedAmountCents(): int
    {
        return $this->receivedAmountCents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getPriceVariancePercent(): float
    {
        return $this->priceVariancePercent;
    }

    public function getQuantityVariancePercent(): float
    {
        return $this->quantityVariancePercent;
    }

    public function getPriceTolerancePercent(): float
    {
        return $this->priceTolerancePercent;
    }

    public function getQuantityTolerancePercent(): float
    {
        return $this->quantityTolerancePercent;
    }

    /**
     * @return array<string, array{
     *     type: string,
     *     field: string,
     *     expected: mixed,
     *     actual: mixed,
     *     variancePercent: float
     * }>
     */
    public function getVarianceDetails(): array
    {
        return $this->varianceDetails;
    }

    public function getFailureReason(): string
    {
        return $this->failureReason;
    }

    public function getFailedAt(): \DateTimeImmutable
    {
        return $this->failedAt;
    }
}
