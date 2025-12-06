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
        private string $vendorBillId,
        private string $tenantId,
        private string $vendorBillNumber,
        private string $purchaseOrderId,
        private string $purchaseOrderNumber,
        private array $matchedGoodsReceiptIds,
        private string $vendorId,
        private int $invoiceAmountCents,
        private int $poAmountCents,
        private int $receivedAmountCents,
        private string $currency,
        private float $priceVariancePercent,
        private float $quantityVariancePercent,
        private bool $withinTolerance,
        private string $matchedBy,
        private \DateTimeImmutable $matchedAt,
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
    public function getMatchedGoodsReceiptIds(): array
    {
        return $this->matchedGoodsReceiptIds;
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

    public function isWithinTolerance(): bool
    {
        return $this->withinTolerance;
    }

    public function getMatchedBy(): string
    {
        return $this->matchedBy;
    }

    public function getMatchedAt(): \DateTimeImmutable
    {
        return $this->matchedAt;
    }
}
