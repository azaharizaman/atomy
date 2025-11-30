<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\Enums\PeriodCloseStatus;

/**
 * Period Close Service interface.
 *
 * Handles month-end and year-end closing procedures.
 */
interface PeriodCloseServiceInterface
{
    /**
     * Close a month-end period.
     *
     * @param array<string, mixed> $options
     */
    public function closeMonth(string $periodId, array $options = []): void;

    /**
     * Close a year-end period.
     *
     * @param array<string, mixed> $options
     */
    public function closeYear(string $fiscalYearId, array $options = []): void;

    /**
     * Reopen a previously closed period.
     */
    public function reopenPeriod(string $periodId, string $reason): void;

    /**
     * Get the close status of a period.
     */
    public function getPeriodCloseStatus(string $periodId): PeriodCloseStatus;

    /**
     * Validate period readiness for close.
     *
     * @return array{ready: bool, issues: string[], checked_at: \DateTimeImmutable}
     */
    public function validatePeriodReadiness(string $periodId): array;

    /**
     * Generate closing journal entries.
     *
     * @return array<string, mixed>
     */
    public function generateClosingEntries(string $periodId): array;

    /**
     * Check if all transactions are posted.
     */
    public function areAllTransactionsPosted(string $periodId): bool;

    /**
     * Verify trial balance is balanced.
     */
    public function verifyTrialBalance(string $periodId): bool;
}
