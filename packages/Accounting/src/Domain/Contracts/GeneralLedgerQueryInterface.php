<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use DateTimeImmutable;

/**
 * Query interface for general ledger data.
 *
 * This interface abstracts access to GL data from the Finance package
 * for statement generation purposes.
 */
interface GeneralLedgerQueryInterface
{
    /**
     * Get trial balance for a period.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @return array<string, array{account_code: string, account_name: string, debit: float, credit: float, balance: float}>
     */
    public function getTrialBalance(string $tenantId, string $periodId): array;

    /**
     * Get account balances at a specific date.
     *
     * @param string $tenantId Tenant identifier
     * @param DateTimeImmutable $asOfDate The date to get balances for
     * @return array<string, array{account_code: string, account_name: string, balance: float, account_type: string}>
     */
    public function getBalancesAsOf(string $tenantId, DateTimeImmutable $asOfDate): array;

    /**
     * Get account movements for a period.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @param string $accountCode Optional account code filter
     * @return array<array{date: string, description: string, debit: float, credit: float, balance: float}>
     */
    public function getAccountMovements(string $tenantId, string $periodId, ?string $accountCode = null): array;

    /**
     * Get income/expense totals for a period (for P&L).
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @return array{revenue: float, expenses: float, net_income: float}
     */
    public function getIncomeExpenseTotals(string $tenantId, string $periodId): array;

    /**
     * Get retained earnings balance.
     *
     * @param string $tenantId Tenant identifier
     * @param DateTimeImmutable $asOfDate Date to calculate up to
     * @return float
     */
    public function getRetainedEarnings(string $tenantId, DateTimeImmutable $asOfDate): float;
}
