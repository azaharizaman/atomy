<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Enums;

/**
 * Type of asset revaluation - Increment or Decrement.
 *
 * @enum
 */
enum RevaluationType: string
{
    case INCREMENT = 'increment';
    case DECREMENT = 'decrement';

    /**
     * Check if this is an increase in value.
     */
    public function isIncrease(): bool
    {
        return $this === self::INCREMENT;
    }

    /**
     * Get the GL account type for this revaluation type.
     */
    public function getGlAccountType(): string
    {
        return match ($this) {
            self::INCREMENT => 'revaluation_reserve',
            self::DECREMENT => 'depreciation_expense',
        };
    }
}
