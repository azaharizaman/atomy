<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Enums;

/**
 * Prorate convention for mid-month/mid-quarter acquisitions.
 *
 * @enum
 */
enum ProrateConvention: string
{
    case FULL_MONTH = 'full_month';
    case DAILY = 'daily';
    case NONE = 'none';
    case HALF_YEAR = 'half_year';
    case MID_QUARTER = 'mid_quarter';

    /**
     * Check if this convention uses daily calculation.
     */
    public function usesDailyCalculation(): bool
    {
        return $this === self::DAILY;
    }

    /**
     * Get the default monthly factor for this convention.
     */
    public function getDefaultMonthlyFactor(): float
    {
        return match ($this) {
            self::FULL_MONTH => 1.0,
            self::DAILY => 0.5, // Assume mid-month
            self::HALF_YEAR => 0.5,
            self::MID_QUARTER => 0.5,
            self::NONE => 1.0,
        };
    }
}
