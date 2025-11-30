<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\ValueObjects\PeriodStatus;
use Nexus\Accounting\Domain\ValueObjects\ReportingPeriod;

/**
 * Contract for period status data access.
 *
 * Implementations must provide access to period status data
 * for period close management.
 */
interface PeriodStatusRepositoryInterface
{
    /**
     * Get the status of a specific period.
     *
     * @param ReportingPeriod $period The period to check
     * @param string|null $tenantId Optional tenant ID for multi-tenant systems
     *
     * @return PeriodStatus The period status
     */
    public function getStatus(ReportingPeriod $period, ?string $tenantId = null): PeriodStatus;

    /**
     * Save the status of a period.
     *
     * @param ReportingPeriod $period The period
     * @param PeriodStatus $status The status to save
     * @param string|null $tenantId Optional tenant ID for multi-tenant systems
     */
    public function saveStatus(
        ReportingPeriod $period,
        PeriodStatus $status,
        ?string $tenantId = null
    ): void;

    /**
     * Get all closed periods.
     *
     * @param string|null $tenantId Optional tenant ID for multi-tenant systems
     *
     * @return array<ReportingPeriod> List of closed periods
     */
    public function getClosedPeriods(?string $tenantId = null): array;

    /**
     * Get the current open period.
     *
     * @param string|null $tenantId Optional tenant ID for multi-tenant systems
     *
     * @return ReportingPeriod|null The current open period or null
     */
    public function getCurrentOpenPeriod(?string $tenantId = null): ?ReportingPeriod;
}
