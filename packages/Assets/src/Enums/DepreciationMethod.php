<?php

declare(strict_types=1);

namespace Nexus\Assets\Enums;

/**
 * Depreciation Method Enum
 *
 * Defines calculation methods for asset depreciation.
 * Tier 1: Straight-Line only
 * Tier 2: Adds Double Declining Balance
 * Tier 3: Adds Units of Production
 */
enum DepreciationMethod: string
{
    case STRAIGHT_LINE = 'straight_line';
    case DOUBLE_DECLINING_BALANCE = 'double_declining_balance';
    case UNITS_OF_PRODUCTION = 'units_of_production';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::STRAIGHT_LINE => 'Straight-Line',
            self::DOUBLE_DECLINING_BALANCE => 'Double Declining Balance',
            self::UNITS_OF_PRODUCTION => 'Units of Production',
        };
    }

    /**
     * Get method description
     */
    public function description(): string
    {
        return match($this) {
            self::STRAIGHT_LINE => 'Constant depreciation over useful life: (Cost - Salvage) / Useful Life',
            self::DOUBLE_DECLINING_BALANCE => 'Accelerated depreciation: Rate × Beginning Book Value, where Rate = 2 / Useful Life',
            self::UNITS_OF_PRODUCTION => 'Usage-based depreciation: (Cost - Salvage) × (Units Consumed / Total Expected Units)',
        };
    }

    /**
     * Get required tier level
     */
    public function getRequiredTier(): string
    {
        return match($this) {
            self::STRAIGHT_LINE => 'basic',
            self::DOUBLE_DECLINING_BALANCE => 'advanced',
            self::UNITS_OF_PRODUCTION => 'enterprise',
        };
    }

    /**
     * Check if method requires unit tracking
     */
    public function requiresUnitTracking(): bool
    {
        return $this === self::UNITS_OF_PRODUCTION;
    }

    /**
     * Check if method is time-based
     */
    public function isTimeBased(): bool
    {
        return match($this) {
            self::STRAIGHT_LINE, self::DOUBLE_DECLINING_BALANCE => true,
            self::UNITS_OF_PRODUCTION => false,
        };
    }

    /**
     * Check if method is accelerated
     */
    public function isAccelerated(): bool
    {
        return $this === self::DOUBLE_DECLINING_BALANCE;
    }
}
