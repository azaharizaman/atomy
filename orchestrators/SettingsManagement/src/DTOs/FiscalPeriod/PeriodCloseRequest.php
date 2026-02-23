<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FiscalPeriod;

/**
 * Request DTO for closing a fiscal period.
 */
final class PeriodCloseRequest
{
    public function __construct(
        public readonly string $periodId,
        public readonly string $tenantId,
        public readonly bool $generateClosingEntries = true,
        public readonly ?string $reason = null,
    ) {}
}
