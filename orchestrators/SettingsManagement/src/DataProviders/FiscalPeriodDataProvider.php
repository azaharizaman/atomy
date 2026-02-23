<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DataProviders;

use Nexus\SettingsManagement\Contracts\FiscalPeriodProviderInterface;

/**
 * Data provider for fiscal period aggregation.
 * Aggregates fiscal periods from the Period package.
 */
final class FiscalPeriodDataProvider implements FiscalPeriodProviderInterface
{
    public function __construct(
        // In production, inject Period package query interfaces here
    ) {}

    public function getPeriod(string $periodId, string $tenantId): ?array
    {
        // In production, this would query the Period package
        // Placeholder implementation
        return null;
    }

    public function getAllPeriods(string $tenantId): array
    {
        // In production, this would query the Period package
        // Placeholder implementation
        return [];
    }

    public function getCurrentPeriod(string $tenantId): ?array
    {
        // In production, this would query the Period package
        // Placeholder implementation
        return null;
    }

    public function getPeriodByDate(\DateTimeInterface $date, string $tenantId): ?array
    {
        // In production, this would query the Period package
        return null;
    }

    public function getCalendarConfig(string $tenantId): ?array
    {
        // In production, this would query the Period package
        return null;
    }

    public function isPeriodOpen(string $periodId, string $tenantId): bool
    {
        // In production, this would query the Period package
        return false;
    }

    public function isAdjustingPeriod(string $periodId, string $tenantId): bool
    {
        // In production, this would query the Period package
        return false;
    }

    public function isPeriodLocked(string $periodId, string $tenantId): bool
    {
        // In production, this would query the Period package
        return false;
    }
}
