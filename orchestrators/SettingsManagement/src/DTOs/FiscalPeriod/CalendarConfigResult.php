<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FiscalPeriod;

/**
 * Result DTO for fiscal calendar configuration.
 */
final class CalendarConfigResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $calendarId = null,
        public readonly ?string $error = null,
    ) {}

    public static function success(string $calendarId): self
    {
        return new self(success: true, calendarId: $calendarId);
    }

    public static function failure(string $error): self
    {
        return new self(success: false, error: $error);
    }
}
