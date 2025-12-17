<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * GR/IR Accrual Data (Goods Receipt / Invoice Receipt)
 * 
 * Tracks the accrual between goods received and invoices received.
 * Essential for accurate period-end financial reporting.
 */
final readonly class GrIrAccrualData
{
    public function __construct(
        public string $accrualId,
        public string $purchaseOrderId,
        public string $purchaseOrderLineId,
        public string $vendorId,
        public string $productId,
        public float $quantity,
        public string $uom,
        public Money $unitCost,
        public Money $totalAccrualAmount,
        public string $accrualStatus, // 'open', 'partially_matched', 'matched', 'written_off'
        public \DateTimeImmutable $goodsReceiptDate,
        public ?string $goodsReceiptId = null,
        public ?string $invoiceId = null,
        public ?\DateTimeImmutable $invoiceDate = null,
        public ?Money $invoiceAmount = null,
        public ?Money $varianceAmount = null,
        public ?string $varianceReason = null,
        public ?\DateTimeImmutable $matchedAt = null,
        public ?string $matchedBy = null,
        public ?string $periodId = null,
        public ?string $glAccountId = null,
        public array $metadata = [],
    ) {}

    /**
     * Create accrual from goods receipt (no invoice yet)
     */
    public static function fromGoodsReceipt(
        string $accrualId,
        string $purchaseOrderId,
        string $purchaseOrderLineId,
        string $vendorId,
        string $productId,
        float $quantity,
        string $uom,
        Money $unitCost,
        string $goodsReceiptId,
        \DateTimeImmutable $goodsReceiptDate,
        ?string $periodId = null,
        ?string $glAccountId = null,
    ): self {
        $totalAmount = $unitCost->multiply($quantity);

        return new self(
            accrualId: $accrualId,
            purchaseOrderId: $purchaseOrderId,
            purchaseOrderLineId: $purchaseOrderLineId,
            vendorId: $vendorId,
            productId: $productId,
            quantity: $quantity,
            uom: $uom,
            unitCost: $unitCost,
            totalAccrualAmount: $totalAmount,
            accrualStatus: 'open',
            goodsReceiptDate: $goodsReceiptDate,
            goodsReceiptId: $goodsReceiptId,
            periodId: $periodId,
            glAccountId: $glAccountId,
        );
    }

    /**
     * Check if accrual is open (no invoice matched)
     */
    public function isOpen(): bool
    {
        return $this->accrualStatus === 'open';
    }

    /**
     * Check if accrual is fully matched
     */
    public function isMatched(): bool
    {
        return $this->accrualStatus === 'matched';
    }

    /**
     * Check if accrual is partially matched
     */
    public function isPartiallyMatched(): bool
    {
        return $this->accrualStatus === 'partially_matched';
    }

    /**
     * Check if accrual was written off
     */
    public function isWrittenOff(): bool
    {
        return $this->accrualStatus === 'written_off';
    }

    /**
     * Check if there's a variance
     */
    public function hasVariance(): bool
    {
        return $this->varianceAmount !== null && !$this->varianceAmount->isZero();
    }

    /**
     * Get days since goods receipt
     */
    public function getDaysSinceReceipt(?\DateTimeImmutable $asOf = null): int
    {
        $checkDate = $asOf ?? new \DateTimeImmutable();
        return (int) $this->goodsReceiptDate->diff($checkDate)->days;
    }

    /**
     * Check if accrual is aged (older than threshold days)
     */
    public function isAged(int $thresholdDays = 30, ?\DateTimeImmutable $asOf = null): bool
    {
        return $this->getDaysSinceReceipt($asOf) > $thresholdDays;
    }

    /**
     * Match accrual with invoice
     */
    public function withInvoiceMatch(
        string $invoiceId,
        \DateTimeImmutable $invoiceDate,
        Money $invoiceAmount,
        string $matchedBy,
        ?\DateTimeImmutable $matchedAt = null,
    ): self {
        $variance = $invoiceAmount->subtract($this->totalAccrualAmount);
        $varianceReason = null;

        if (!$variance->isZero()) {
            $varianceReason = $variance->isPositive() ? 'invoice_higher' : 'invoice_lower';
        }

        return new self(
            accrualId: $this->accrualId,
            purchaseOrderId: $this->purchaseOrderId,
            purchaseOrderLineId: $this->purchaseOrderLineId,
            vendorId: $this->vendorId,
            productId: $this->productId,
            quantity: $this->quantity,
            uom: $this->uom,
            unitCost: $this->unitCost,
            totalAccrualAmount: $this->totalAccrualAmount,
            accrualStatus: 'matched',
            goodsReceiptDate: $this->goodsReceiptDate,
            goodsReceiptId: $this->goodsReceiptId,
            invoiceId: $invoiceId,
            invoiceDate: $invoiceDate,
            invoiceAmount: $invoiceAmount,
            varianceAmount: $variance,
            varianceReason: $varianceReason,
            matchedAt: $matchedAt ?? new \DateTimeImmutable(),
            matchedBy: $matchedBy,
            periodId: $this->periodId,
            glAccountId: $this->glAccountId,
            metadata: $this->metadata,
        );
    }

    /**
     * Write off accrual
     */
    public function withWriteOff(
        string $reason,
        string $writtenOffBy,
        ?\DateTimeImmutable $writtenOffAt = null,
    ): self {
        return new self(
            accrualId: $this->accrualId,
            purchaseOrderId: $this->purchaseOrderId,
            purchaseOrderLineId: $this->purchaseOrderLineId,
            vendorId: $this->vendorId,
            productId: $this->productId,
            quantity: $this->quantity,
            uom: $this->uom,
            unitCost: $this->unitCost,
            totalAccrualAmount: $this->totalAccrualAmount,
            accrualStatus: 'written_off',
            goodsReceiptDate: $this->goodsReceiptDate,
            goodsReceiptId: $this->goodsReceiptId,
            invoiceId: $this->invoiceId,
            invoiceDate: $this->invoiceDate,
            invoiceAmount: $this->invoiceAmount,
            varianceAmount: $this->totalAccrualAmount, // Full amount as variance
            varianceReason: $reason,
            matchedAt: $writtenOffAt ?? new \DateTimeImmutable(),
            matchedBy: $writtenOffBy,
            periodId: $this->periodId,
            glAccountId: $this->glAccountId,
            metadata: array_merge($this->metadata, ['written_off' => true]),
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'accrual_id' => $this->accrualId,
            'purchase_order_id' => $this->purchaseOrderId,
            'purchase_order_line_id' => $this->purchaseOrderLineId,
            'vendor_id' => $this->vendorId,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'uom' => $this->uom,
            'unit_cost' => $this->unitCost->toArray(),
            'total_accrual_amount' => $this->totalAccrualAmount->toArray(),
            'accrual_status' => $this->accrualStatus,
            'goods_receipt_date' => $this->goodsReceiptDate->format('Y-m-d'),
            'goods_receipt_id' => $this->goodsReceiptId,
            'invoice_id' => $this->invoiceId,
            'invoice_date' => $this->invoiceDate?->format('Y-m-d'),
            'invoice_amount' => $this->invoiceAmount?->toArray(),
            'variance_amount' => $this->varianceAmount?->toArray(),
            'variance_reason' => $this->varianceReason,
            'matched_at' => $this->matchedAt?->format('c'),
            'matched_by' => $this->matchedBy,
            'period_id' => $this->periodId,
            'gl_account_id' => $this->glAccountId,
            'days_since_receipt' => $this->getDaysSinceReceipt(),
            'is_aged' => $this->isAged(),
        ];
    }
}
