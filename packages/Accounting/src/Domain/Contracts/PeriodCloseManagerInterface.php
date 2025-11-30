<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\ValueObjects\CloseResult;
use Nexus\Accounting\Domain\ValueObjects\PeriodStatus;
use Nexus\Accounting\Domain\ValueObjects\ReportingPeriod;

/**
 * Contract for managing accounting period close operations.
 *
 * Implementations must handle the complete period close lifecycle including
 * soft close (reversible) and hard close (permanent) operations.
 */
interface PeriodCloseManagerInterface
{
    /**
     * Initiate a soft close for the specified period.
     *
     * A soft close prevents new transactions but allows reversals
     * and corrections. It can be reopened if needed.
     *
     * @param ReportingPeriod $period The period to soft close
     * @param string|null $reason Optional reason for the close
     *
     * @return CloseResult The result of the close operation
     *
     * @throws \RuntimeException If the period cannot be soft closed
     */
    public function softClose(ReportingPeriod $period, ?string $reason = null): CloseResult;

    /**
     * Perform a hard close for the specified period.
     *
     * A hard close is permanent and prevents any further changes
     * to the period. This action cannot be reversed.
     *
     * @param ReportingPeriod $period The period to hard close
     * @param string|null $reason Optional reason for the close
     *
     * @return CloseResult The result of the close operation
     *
     * @throws \RuntimeException If the period cannot be hard closed
     */
    public function hardClose(ReportingPeriod $period, ?string $reason = null): CloseResult;

    /**
     * Reopen a soft-closed period.
     *
     * @param ReportingPeriod $period The period to reopen
     * @param string $reason The reason for reopening
     *
     * @return CloseResult The result of the reopen operation
     *
     * @throws \RuntimeException If the period cannot be reopened
     */
    public function reopen(ReportingPeriod $period, string $reason): CloseResult;

    /**
     * Get the current status of a period.
     *
     * @param ReportingPeriod $period The period to check
     *
     * @return PeriodStatus The current status of the period
     */
    public function getStatus(ReportingPeriod $period): PeriodStatus;

    /**
     * Check if transactions can be posted to the specified period.
     *
     * @param ReportingPeriod $period The period to check
     *
     * @return bool True if transactions can be posted
     */
    public function canPostTransactions(ReportingPeriod $period): bool;
}
