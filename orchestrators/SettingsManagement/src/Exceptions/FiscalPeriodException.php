<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Exceptions;

/**
 * Base exception for fiscal period operations.
 */
class FiscalPeriodException extends SettingsManagementException
{
}

/**
 * Exception thrown when a fiscal period is not found.
 */
class FiscalPeriodNotFoundException extends FiscalPeriodException
{
    public static function forId(string $periodId, ?string $tenantId = null): self
    {
        return new self(
            message: "Fiscal period '{$periodId}' not found" . ($tenantId ? " for tenant {$tenantId}" : ''),
            context: ['period_id' => $periodId, 'tenant_id' => $tenantId]
        );
    }
}

/**
 * Exception thrown when fiscal periods overlap.
 */
class FiscalPeriodOverlapException extends FiscalPeriodException
{
    public static function periodsOverlap(array $overlappingPeriods): self
    {
        return new self(
            message: 'New period overlaps with existing periods: ' . implode(', ', array_column($overlappingPeriods, 'period_name')),
            context: ['overlapping_periods' => $overlappingPeriods]
        );
    }
}

/**
 * Exception thrown when fiscal period is locked.
 */
class FiscalPeriodLockedException extends FiscalPeriodException
{
    public static function periodLocked(string $periodId): self
    {
        return new self(
            message: "Fiscal period '{$periodId}' is locked",
            context: ['period_id' => $periodId]
        );
    }
}
