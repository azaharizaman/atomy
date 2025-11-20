<?php

declare(strict_types=1);

namespace Nexus\Localization\Enums;

/**
 * Currency symbol position relative to the amount.
 *
 * Defines how currency symbols are displayed in formatted amounts.
 */
enum CurrencyPosition: string
{
    case Before = 'before';                  // $100
    case After = 'after';                    // 100$
    case BeforeWithSpace = 'before_space';   // $ 100
    case AfterWithSpace = 'after_space';     // 100 $

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Before => 'Before Amount ($100)',
            self::After => 'After Amount (100$)',
            self::BeforeWithSpace => 'Before with Space ($ 100)',
            self::AfterWithSpace => 'After with Space (100 $)',
        };
    }

    /**
     * Format a currency amount with the symbol in the correct position.
     */
    public function format(string $symbol, string $formattedAmount): string
    {
        return match ($this) {
            self::Before => $symbol . $formattedAmount,
            self::After => $formattedAmount . $symbol,
            self::BeforeWithSpace => $symbol . ' ' . $formattedAmount,
            self::AfterWithSpace => $formattedAmount . ' ' . $symbol,
        };
    }

    /**
     * Check if symbol appears before amount.
     */
    public function isSymbolBefore(): bool
    {
        return in_array($this, [self::Before, self::BeforeWithSpace], true);
    }

    /**
     * Check if symbol appears after amount.
     */
    public function isSymbolAfter(): bool
    {
        return in_array($this, [self::After, self::AfterWithSpace], true);
    }

    /**
     * Check if space is used between symbol and amount.
     */
    public function hasSpace(): bool
    {
        return in_array($this, [self::BeforeWithSpace, self::AfterWithSpace], true);
    }
}
