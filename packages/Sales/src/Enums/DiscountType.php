<?php

declare(strict_types=1);

namespace Nexus\Sales\Enums;

/**
 * Discount type enum.
 * 
 * Defines how discount values are interpreted.
 */
enum DiscountType: string
{
    case PERCENTAGE = 'percentage';     // Discount as percentage (e.g., 10%)
    case FIXED_AMOUNT = 'fixed_amount'; // Discount as fixed monetary amount

    /**
     * Check if discount is percentage-based.
     */
    public function isPercentage(): bool
    {
        return $this === self::PERCENTAGE;
    }

    /**
     * Check if discount is fixed amount.
     */
    public function isFixedAmount(): bool
    {
        return $this === self::FIXED_AMOUNT;
    }
}
