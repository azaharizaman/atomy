<?php

declare(strict_types=1);

namespace Nexus\Localization\Services;

use DateTimeInterface;
use IntlDateFormatter;
use Nexus\Localization\Contracts\LocaleRepositoryInterface;
use Nexus\Localization\Exceptions\MissingRequirementException;
use Nexus\Localization\ValueObjects\DateTimeFormat;
use Nexus\Localization\ValueObjects\Locale;

/**
 * Date/time formatter service using PHP Intl extension.
 *
 * Provides locale-aware date and time formatting with CLDR patterns.
 */
final class DateTimeFormatter
{
    public function __construct(
        private readonly LocaleRepositoryInterface $localeRepository,
    ) {
        if (!extension_loaded('intl')) {
            throw new MissingRequirementException('PHP Intl extension');
        }
    }

    /**
     * Format a datetime for the given locale.
     *
     * @param DateTimeInterface $datetime The datetime to format
     * @param Locale $locale Target locale
     * @param string $dateStyle Date format style ('short', 'medium', 'long', 'full') or custom pattern
     * @param string $timeStyle Time format style ('short', 'medium', 'long', 'full', 'none')
     * @return string Formatted datetime
     */
    public function format(
        DateTimeInterface $datetime,
        Locale $locale,
        string $dateStyle = 'medium',
        string $timeStyle = 'short'
    ): string {
        $dateType = $this->getIntlDateType($dateStyle);
        $timeType = $this->getIntlDateType($timeStyle);

        $formatter = new IntlDateFormatter(
            $locale->code(),
            $dateType,
            $timeType,
            $datetime->getTimezone(),
        );

        $result = $formatter->format($datetime);

        return $result !== false ? $result : $datetime->format('Y-m-d H:i:s');
    }

    /**
     * Format a date (no time) for the given locale.
     */
    public function formatDate(
        DateTimeInterface $datetime,
        Locale $locale,
        string $style = 'medium'
    ): string {
        return $this->format($datetime, $locale, $style, 'none');
    }

    /**
     * Format a time (no date) for the given locale.
     */
    public function formatTime(
        DateTimeInterface $datetime,
        Locale $locale,
        string $style = 'short'
    ): string {
        $timeType = $this->getIntlDateType($style);

        $formatter = new IntlDateFormatter(
            $locale->code(),
            IntlDateFormatter::NONE,
            $timeType,
            $datetime->getTimezone(),
        );

        $result = $formatter->format($datetime);

        return $result !== false ? $result : $datetime->format('H:i:s');
    }

    /**
     * Format with custom pattern from DateTimeFormat value object.
     */
    public function formatWithPattern(
        DateTimeInterface $datetime,
        Locale $locale,
        DateTimeFormat $format
    ): string {
        $formatter = new IntlDateFormatter(
            $locale->code(),
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            $datetime->getTimezone(),
        );

        $formatter->setPattern($format->datetimePattern);

        $result = $formatter->format($datetime);

        return $result !== false ? $result : $datetime->format('Y-m-d H:i:s');
    }

    /**
     * Format date with custom pattern.
     */
    public function formatDateWithPattern(
        DateTimeInterface $datetime,
        Locale $locale,
        string $pattern
    ): string {
        $formatter = new IntlDateFormatter(
            $locale->code(),
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            $datetime->getTimezone(),
        );

        $formatter->setPattern($pattern);

        $result = $formatter->format($datetime);

        return $result !== false ? $result : $datetime->format($pattern);
    }

    /**
     * Convert style string to IntlDateFormatter constant.
     */
    private function getIntlDateType(string $style): int
    {
        return match (strtolower($style)) {
            'none' => IntlDateFormatter::NONE,
            'short' => IntlDateFormatter::SHORT,
            'medium' => IntlDateFormatter::MEDIUM,
            'long' => IntlDateFormatter::LONG,
            'full' => IntlDateFormatter::FULL,
            default => IntlDateFormatter::MEDIUM,
        };
    }
}
