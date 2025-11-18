<?php

declare(strict_types=1);

namespace Nexus\Accounting\Contracts;

use Nexus\Accounting\Core\Enums\PeriodCloseStatus;

/**
 * Period close operations contract.
 *
 * Handles month-end and year-end closing procedures.
 */
interface PeriodCloseServiceInterface
{
    /**
     * Close a month-end period.
     *
     * @param array<string, mixed> $options Closing options
     * @throws \Nexus\Accounting\Exceptions\PeriodNotClosedException
     */
    public function closeMonth(string $periodId, array $options = []): void;

    /**
     * Close a year-end period.
     *
     * @param array<string, mixed> $options Closing options
     * @throws \Nexus\Accounting\Exceptions\PeriodNotClosedException
     */
    public function closeYear(string $fiscalYearId, array $options = []): void;

    /**
     * Reopen a previously closed period.
     *
     * @throws \Nexus\Accounting\Exceptions\InvalidReportingPeriodException
     */
    public function reopenPeriod(string $periodId, string $reason): void;

    /**
     * Get the close status of a period.
     */
    public function getPeriodCloseStatus(string $periodId): PeriodCloseStatus;

    /**
     * Validate if a period is ready to be closed.
     *
     * @return array<string, mixed> Validation results with issues
     */
    public function validatePeriodReadiness(string $periodId): array;

    /**
     * Generate closing entries for revenue/expense accounts.
     *
     * @return array<string, mixed> Generated entry details
     */
    public function generateClosingEntries(string $periodId): array;

    /**
     * Check if all transactions are posted for the period.
     */
    public function areAllTransactionsPosted(string $periodId): bool;

    /**
     * Verify trial balance for the period.
     */
    public function verifyTrialBalance(string $periodId): bool;
}
