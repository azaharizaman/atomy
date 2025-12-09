<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Reasons for creating credit/debit memos.
 */
enum MemoReason: string
{
    // Credit memo reasons (vendor owes us)
    case RETURN_GOODS = 'return_goods';
    case PRICE_ADJUSTMENT = 'price_adjustment';
    case BILLING_ERROR = 'billing_error';
    case VOLUME_REBATE = 'volume_rebate';
    case EARLY_PAYMENT_DISCOUNT = 'early_payment_discount';
    case QUALITY_ISSUE = 'quality_issue';
    case DAMAGED_GOODS = 'damaged_goods';
    case SHORT_SHIPMENT = 'short_shipment';

    // Debit memo reasons (we owe vendor more)
    case PRICE_INCREASE = 'price_increase';
    case ADDITIONAL_CHARGES = 'additional_charges';
    case FREIGHT_ADJUSTMENT = 'freight_adjustment';
    case TAX_ADJUSTMENT = 'tax_adjustment';
    case LATE_FEE = 'late_fee';

    /**
     * Check if this reason typically results in a credit memo.
     */
    public function isCreditMemoReason(): bool
    {
        return match ($this) {
            self::RETURN_GOODS,
            self::PRICE_ADJUSTMENT,
            self::BILLING_ERROR,
            self::VOLUME_REBATE,
            self::EARLY_PAYMENT_DISCOUNT,
            self::QUALITY_ISSUE,
            self::DAMAGED_GOODS,
            self::SHORT_SHIPMENT => true,
            default => false,
        };
    }

    /**
     * Check if this reason requires documentation/approval.
     */
    public function requiresApproval(): bool
    {
        return match ($this) {
            self::RETURN_GOODS,
            self::QUALITY_ISSUE,
            self::DAMAGED_GOODS,
            self::PRICE_ADJUSTMENT,
            self::PRICE_INCREASE => true,
            default => false,
        };
    }

    /**
     * Get human-readable description.
     */
    public function description(): string
    {
        return match ($this) {
            self::RETURN_GOODS => 'Return of goods to vendor',
            self::PRICE_ADJUSTMENT => 'Price adjustment (usually downward)',
            self::BILLING_ERROR => 'Correction of billing error',
            self::VOLUME_REBATE => 'Volume-based rebate from vendor',
            self::EARLY_PAYMENT_DISCOUNT => 'Discount for early payment',
            self::QUALITY_ISSUE => 'Compensation for quality issues',
            self::DAMAGED_GOODS => 'Credit for damaged goods received',
            self::SHORT_SHIPMENT => 'Credit for under-shipped quantity',
            self::PRICE_INCREASE => 'Price increase from vendor',
            self::ADDITIONAL_CHARGES => 'Additional charges not on original invoice',
            self::FREIGHT_ADJUSTMENT => 'Adjustment to freight charges',
            self::TAX_ADJUSTMENT => 'Adjustment to tax amounts',
            self::LATE_FEE => 'Late payment fee',
        };
    }
}
