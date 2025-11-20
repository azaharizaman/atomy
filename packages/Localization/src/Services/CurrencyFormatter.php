<?php

declare(strict_types=1);

namespace Nexus\Localization\Services;

use Nexus\Localization\Contracts\LocaleRepositoryInterface;
use Nexus\Localization\Exceptions\MissingRequirementException;
use Nexus\Localization\ValueObjects\CurrencyFormat;
use Nexus\Localization\ValueObjects\Locale;

/**
 * Currency formatter service for locale-aware monetary amounts.
 *
 * Integrates with Nexus\Finance\ValueObjects\Money for formatting.
 */
final class CurrencyFormatter
{
    public function __construct(
        private readonly LocaleRepositoryInterface $localeRepository,
        private readonly NumberFormatter $numberFormatter,
    ) {
        if (!extension_loaded('intl')) {
            throw new MissingRequirementException('PHP Intl extension');
        }
    }

    /**
     * Format a monetary amount with currency symbol.
     *
     * @param string $amount High-precision amount string (e.g., from Money VO)
     * @param string $currencyCode ISO 4217 currency code (e.g., 'MYR', 'USD')
     * @param Locale $locale Target locale
     * @param int $decimalPlaces Number of decimal places (default: 2)
     * @return string Formatted currency amount
     */
    public function format(
        string $amount,
        string $currencyCode,
        Locale $locale,
        int $decimalPlaces = 2
    ): string {
        $settings = $this->localeRepository->getLocaleSettings($locale);

        $format = CurrencyFormat::fromLocaleSettings($settings, $currencyCode, $decimalPlaces);

        // Format the number part using NumberFormatter
        $formattedNumber = $this->numberFormatter->format(
            (float) $amount,
            $locale,
            new \Nexus\Localization\ValueObjects\NumberFormat(
                $settings->decimalSeparator,
                $settings->thousandsSeparator,
                $decimalPlaces,
            )
        );

        // Apply currency symbol in correct position
        return $format->format($formattedNumber);
    }

    /**
     * Format using PHP's native currency formatter.
     *
     * This method uses IntlNumberFormatter's CURRENCY mode, which
     * automatically applies the locale's currency formatting rules.
     */
    public function formatWithIntl(
        float $amount,
        string $currencyCode,
        Locale $locale
    ): string {
        $formatter = new \NumberFormatter($locale->code(), \NumberFormatter::CURRENCY);

        $result = $formatter->formatCurrency($amount, $currencyCode);

        if ($result === false) {
            // Fallback to manual formatting
            return $this->format((string) $amount, $currencyCode, $locale);
        }

        return $result;
    }

    /**
     * Get currency symbol for a locale.
     */
    public function getCurrencySymbol(string $currencyCode, Locale $locale): string
    {
        $settings = $this->localeRepository->getLocaleSettings($locale);

        return $settings->getCurrencySymbol($currencyCode) ?? $currencyCode;
    }
}
