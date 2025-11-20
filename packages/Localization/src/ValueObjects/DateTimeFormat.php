<?php

declare(strict_types=1);

namespace Nexus\Localization\ValueObjects;

/**
 * Date/time format configuration for a locale.
 *
 * Defines format patterns for date, time, and datetime rendering.
 */
final readonly class DateTimeFormat
{
    public function __construct(
        public string $datePattern,
        public string $timePattern,
        public string $datetimePattern,
    ) {
    }

    /**
     * Create from locale settings.
     */
    public static function fromLocaleSettings(LocaleSettings $settings): self
    {
        return new self(
            $settings->dateFormat,
            $settings->timeFormat,
            $settings->datetimeFormat,
        );
    }

    /**
     * Create with custom patterns.
     */
    public static function custom(
        string $datePattern,
        string $timePattern,
        ?string $datetimePattern = null
    ): self {
        return new self(
            $datePattern,
            $timePattern,
            $datetimePattern ?? "$datePattern $timePattern",
        );
    }
}
