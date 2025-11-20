<?php

declare(strict_types=1);

namespace Nexus\Localization\ValueObjects;

/**
 * Number format configuration for a locale.
 *
 * Defines decimal separator, thousands separator, and number of decimal places.
 */
final readonly class NumberFormat
{
    public function __construct(
        public string $decimalSeparator = '.',
        public string $thousandsSeparator = ',',
        public int $decimalPlaces = 2,
    ) {
    }

    /**
     * Create from locale settings.
     */
    public static function fromLocaleSettings(LocaleSettings $settings, int $decimalPlaces = 2): self
    {
        return new self(
            $settings->decimalSeparator,
            $settings->thousandsSeparator,
            $decimalPlaces,
        );
    }
}
