<?php

declare(strict_types=1);

namespace Nexus\Localization\Services;

use Nexus\Localization\Contracts\LocaleRepositoryInterface;
use Nexus\Localization\Exceptions\MissingRequirementException;
use Nexus\Localization\ValueObjects\Locale;
use Nexus\Localization\ValueObjects\NumberFormat;
use NumberFormatter as PhpNumberFormatter;

/**
 * Number formatter service using PHP Intl extension.
 *
 * Provides locale-aware number formatting with CLDR-authoritative rules.
 */
final class NumberFormatter
{
    public function __construct(
        private readonly LocaleRepositoryInterface $localeRepository,
    ) {
        if (!extension_loaded('intl')) {
            throw new MissingRequirementException('PHP Intl extension');
        }
    }

    /**
     * Format a number for the given locale.
     *
     * @param float|int $value The number to format
     * @param Locale $locale Target locale
     * @param NumberFormat|null $format Optional custom format (uses locale default if null)
     * @return string Formatted number
     */
    public function format(
        float|int $value,
        Locale $locale,
        ?NumberFormat $format = null
    ): string {
        $settings = $this->localeRepository->getLocaleSettings($locale);

        $format ??= NumberFormat::fromLocaleSettings($settings);

        $formatter = new PhpNumberFormatter($locale->code(), PhpNumberFormatter::DECIMAL);

        // Set decimal separator
        $formatter->setSymbol(PhpNumberFormatter::DECIMAL_SEPARATOR_SYMBOL, $format->decimalSeparator);

        // Set thousands separator
        $formatter->setSymbol(PhpNumberFormatter::GROUPING_SEPARATOR_SYMBOL, $format->thousandsSeparator);

        // Set decimal places
        $formatter->setAttribute(PhpNumberFormatter::MIN_FRACTION_DIGITS, $format->decimalPlaces);
        $formatter->setAttribute(PhpNumberFormatter::MAX_FRACTION_DIGITS, $format->decimalPlaces);

        $result = $formatter->format($value);

        if ($result === false) {
            // Fallback to basic formatting if Intl fails
            return number_format(
                (float) $value,
                $format->decimalPlaces,
                $format->decimalSeparator,
                $format->thousandsSeparator
            );
        }

        return $result;
    }

    /**
     * Format a percentage for the given locale.
     */
    public function formatPercentage(
        float $value,
        Locale $locale,
        int $decimalPlaces = 2
    ): string {
        $formatter = new PhpNumberFormatter($locale->code(), PhpNumberFormatter::PERCENT);
        $formatter->setAttribute(PhpNumberFormatter::MIN_FRACTION_DIGITS, $decimalPlaces);
        $formatter->setAttribute(PhpNumberFormatter::MAX_FRACTION_DIGITS, $decimalPlaces);

        $result = $formatter->format($value);

        return $result !== false ? $result : ($value * 100) . '%';
    }

    /**
     * Parse a localized number string to float.
     *
     * @throws \Nexus\Localization\Exceptions\LocalizationException
     */
    public function parse(string $localizedNumber, Locale $locale): float
    {
        $formatter = new PhpNumberFormatter($locale->code(), PhpNumberFormatter::DECIMAL);

        $result = $formatter->parse($localizedNumber);

        if ($result === false) {
            // Try fallback parsing by replacing locale separators
            $settings = $this->localeRepository->getLocaleSettings($locale);

            $normalized = str_replace($settings->thousandsSeparator, '', $localizedNumber);
            $normalized = str_replace($settings->decimalSeparator, '.', $normalized);

            return (float) $normalized;
        }

        return (float) $result;
    }
}
