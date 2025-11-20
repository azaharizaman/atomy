<?php

declare(strict_types=1);

namespace Nexus\Localization\ValueObjects;

use Nexus\Localization\Enums\CurrencyPosition;
use Nexus\Localization\Enums\TextDirection;

/**
 * Immutable locale settings containing all CLDR formatting rules.
 *
 * This value object is cached and must remain immutable to ensure cache safety.
 * All properties are readonly to prevent mutations.
 */
final readonly class LocaleSettings
{
    /**
     * @param array<string, mixed> $metadata Additional locale-specific data (currency_symbols, etc.)
     */
    public function __construct(
        public Locale $locale,
        public string $name,
        public string $nativeName,
        public TextDirection $textDirection,
        public string $decimalSeparator,
        public string $thousandsSeparator,
        public string $dateFormat,
        public string $timeFormat,
        public string $datetimeFormat,
        public CurrencyPosition $currencyPosition,
        public int $firstDayOfWeek,
        public array $metadata,
    ) {
    }

    /**
     * Get currency symbol for a specific currency code from metadata.
     */
    public function getCurrencySymbol(string $currencyCode): ?string
    {
        return $this->metadata['currency_symbols'][$currencyCode] ?? null;
    }

    /**
     * Check if locale uses right-to-left text direction.
     */
    public function isRightToLeft(): bool
    {
        return $this->textDirection->isRightToLeft();
    }

    /**
     * Get all currency symbols defined in metadata.
     *
     * @return array<string, string>
     */
    public function getAllCurrencySymbols(): array
    {
        return $this->metadata['currency_symbols'] ?? [];
    }
}
