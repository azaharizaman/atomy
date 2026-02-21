<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Enums;

/**
 * Depreciation method types supported by the package.
 *
 * @enum
 */
enum DepreciationMethodType: string
{
    case STRAIGHT_LINE = 'straight_line';
    case STRAIGHT_LINE_DAILY = 'straight_line_daily';
    case DOUBLE_DECLINING = 'double_declining';
    case DECLINING_150 = 'declining_150';
    case SUM_OF_YEARS = 'sum_of_years';
    case UNITS_OF_PRODUCTION = 'units_of_production';
    case ANNUITY = 'annuity';
    case MACRS = 'macrs';
    case BONUS = 'bonus';

    /**
     * Check if this method is accelerated depreciation.
     */
    public function isAccelerated(): bool
    {
        return match ($this) {
            self::DOUBLE_DECLINING, self::DECLINING_150, self::SUM_OF_YEARS => true,
            default => false,
        };
    }

    /**
     * Check if this method requires useful life.
     */
    public function requiresUsefulLife(): bool
    {
        return match ($this) {
            self::UNITS_OF_PRODUCTION => false,
            default => true,
        };
    }

    /**
     * Check if this method supports salvage value.
     */
    public function supportsSalvageValue(): bool
    {
        return match ($this) {
            self::DOUBLE_DECLINING, self::DECLINING_150 => false,
            default => true,
        };
    }

    /**
     * Get the tier level for this method.
     */
    public function getTierLevel(): int
    {
        return match ($this) {
            self::STRAIGHT_LINE, self::STRAIGHT_LINE_DAILY => 1,
            self::DOUBLE_DECLINING, self::DECLINING_150, self::SUM_OF_YEARS => 2,
            self::UNITS_OF_PRODUCTION, self::ANNUITY, self::MACRS, self::BONUS => 3,
        };
    }
}
