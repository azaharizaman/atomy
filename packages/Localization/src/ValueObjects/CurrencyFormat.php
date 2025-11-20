<?php

declare(strict_types=1);

namespace Nexus\Localization\ValueObjects;

use Nexus\Localization\Enums\CurrencyPosition;

/**
 * Currency format configuration for a locale.
 *
 * Defines how currency symbols are positioned and formatted.
 */
final readonly class CurrencyFormat
{
    public function __construct(
        public string $symbol,
        public CurrencyPosition $position,
        public int $decimalPlaces = 2,
    ) {
    }

    /**
     * Create from locale settings and currency code.
     */
    public static function fromLocaleSettings(
        LocaleSettings $settings,
        string $currencyCode,
        int $decimalPlaces = 2
    ): self {
        $symbol = $settings->getCurrencySymbol($currencyCode) ?? $currencyCode;

        return new self(
            $symbol,
            $settings->currencyPosition,
            $decimalPlaces,
        );
    }

    /**
     * Format an amount with the currency symbol.
     */
    public function format(string $formattedAmount): string
    {
        return $this->position->format($this->symbol, $formattedAmount);
    }
}
