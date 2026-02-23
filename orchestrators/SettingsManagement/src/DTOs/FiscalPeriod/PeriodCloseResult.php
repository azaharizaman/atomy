<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FiscalPeriod;

/**
 * Result DTO for period closure operation.
 */
final class PeriodCloseResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $periodId = null,
        public readonly array $closingEntries = [],
        public readonly ?string $error = null,
    ) {}

    public static function success(string $periodId, array $closingEntries = []): self
    {
        return new self(success: true, periodId: $periodId, closingEntries: $closingEntries);
    }

    public static function failure(string $error): self
    {
        return new self(success: false, error: $error);
    }
}
