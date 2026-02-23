<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Contracts;

/**
 * Interface for fiscal period data provider.
 * Aggregates fiscal periods from the Period package.
 */
interface FiscalPeriodProviderInterface
{
    /**
     * Get fiscal period by ID.
     */
    public function getPeriod(string $periodId, string $tenantId): ?array;

    /**
     * Get all fiscal periods for a tenant.
     */
    public function getAllPeriods(string $tenantId): array;

    /**
     * Get current open period for a tenant.
     */
    public function getCurrentPeriod(string $tenantId): ?array;

    /**
     * Get period by date.
     */
    public function getPeriodByDate(\DateTimeInterface $date, string $tenantId): ?array;

    /**
     * Get fiscal calendar configuration for a tenant.
     */
    public function getCalendarConfig(string $tenantId): ?array;

    /**
     * Check if period is open for transactions.
     */
    public function isPeriodOpen(string $periodId, string $tenantId): bool;

    /**
     * Check if period allows adjustments.
     */
    public function isAdjustingPeriod(string $periodId, string $tenantId): bool;

    /**
     * Check if period is locked.
     */
    public function isPeriodLocked(string $periodId, string $tenantId): bool;
}
