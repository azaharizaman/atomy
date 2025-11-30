<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\Entities\PeriodCloseRecord;

/**
 * Query interface for period close records.
 *
 * Follows CQRS pattern - read operations only.
 */
interface PeriodCloseQueryInterface
{
    /**
     * Find a period close record by ID.
     *
     * @param string $id Period close record ID
     * @return PeriodCloseRecord|null
     */
    public function findById(string $id): ?PeriodCloseRecord;

    /**
     * Find period close record by tenant and period.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @return PeriodCloseRecord|null
     */
    public function findByTenantAndPeriod(string $tenantId, string $periodId): ?PeriodCloseRecord;

    /**
     * Get all period close records for a tenant.
     *
     * @param string $tenantId Tenant identifier
     * @return array<PeriodCloseRecord>
     */
    public function findAllByTenant(string $tenantId): array;

    /**
     * Check if a period is closed.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @return bool
     */
    public function isPeriodClosed(string $tenantId, string $periodId): bool;

    /**
     * Get the most recent closed period for a tenant.
     *
     * @param string $tenantId Tenant identifier
     * @return PeriodCloseRecord|null
     */
    public function findLatestClosed(string $tenantId): ?PeriodCloseRecord;
}
