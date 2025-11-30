<?php

declare(strict_types=1);

namespace Nexus\Receivable\Enums;

/**
 * Credit Terms (Payment Terms)
 *
 * Defines standard payment terms and early payment discount structures.
 */
enum CreditTerm: string
{
    case NET_15 = 'net_15';
    case NET_30 = 'net_30';
    case NET_45 = 'net_45';
    case NET_60 = 'net_60';
    case NET_90 = 'net_90';
    case TWO_TEN_NET_30 = '2_10_net_30';       // 2% discount if paid within 10 days, net 30
    case ONE_FIFTEEN_NET_45 = '1_15_net_45';   // 1% discount if paid within 15 days, net 45
    case CASH_ON_DELIVERY = 'cod';
    case DUE_ON_RECEIPT = 'due_on_receipt';
    case PREPAYMENT_REQUIRED = 'prepayment';

    /**
     * Get the number of days until payment is due
     */
    public function getDueDays(): int
    {
        return match ($this) {
            self::NET_15 => 15,
            self::NET_30 => 30,
            self::NET_45 => 45,
            self::NET_60 => 60,
            self::NET_90 => 90,
            self::TWO_TEN_NET_30 => 30,
            self::ONE_FIFTEEN_NET_45 => 45,
            self::CASH_ON_DELIVERY, self::DUE_ON_RECEIPT => 0,
            self::PREPAYMENT_REQUIRED => -1, // Must be paid before delivery
        };
    }

    /**
     * Get early payment discount percentage (if applicable)
     */
    public function getDiscountPercent(): float
    {
        return match ($this) {
            self::TWO_TEN_NET_30 => 2.0,
            self::ONE_FIFTEEN_NET_45 => 1.0,
            default => 0.0,
        };
    }

    /**
     * Get early payment discount days (if applicable)
     */
    public function getDiscountDays(): int
    {
        return match ($this) {
            self::TWO_TEN_NET_30 => 10,
            self::ONE_FIFTEEN_NET_45 => 15,
            default => 0,
        };
    }

    /**
     * Does this term offer an early payment discount?
     */
    public function hasDiscount(): bool
    {
        return $this->getDiscountPercent() > 0;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::NET_15 => 'Net 15 Days',
            self::NET_30 => 'Net 30 Days',
            self::NET_45 => 'Net 45 Days',
            self::NET_60 => 'Net 60 Days',
            self::NET_90 => 'Net 90 Days',
            self::TWO_TEN_NET_30 => '2/10 Net 30',
            self::ONE_FIFTEEN_NET_45 => '1/15 Net 45',
            self::CASH_ON_DELIVERY => 'Cash on Delivery (COD)',
            self::DUE_ON_RECEIPT => 'Due on Receipt',
            self::PREPAYMENT_REQUIRED => 'Prepayment Required',
        };
    }
}
