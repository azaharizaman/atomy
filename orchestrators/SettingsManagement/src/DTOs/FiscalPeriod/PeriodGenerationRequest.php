<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FiscalPeriod;

/**
 * Request DTO for generating fiscal periods.
 */
final class PeriodGenerationRequest
{
    public function __construct(
        public readonly string $calendarId,
        public readonly string $tenantId,
        public readonly int $fiscalYear,
        public readonly int $numberOfPeriods,
    ) {}
}
