<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FiscalPeriod;

/**
 * Request DTO for configuring fiscal calendar.
 */
final class CalendarConfigRequest
{
    public function __construct(
        public readonly string $tenantId,
        public readonly \DateTimeInterface $fiscalYearStart,
        public readonly PeriodType $periodType,
        public readonly string $namingConvention,
        public readonly bool $yearEndClosing = true,
    ) {}
}
