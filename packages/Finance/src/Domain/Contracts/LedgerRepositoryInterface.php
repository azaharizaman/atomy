<?php

declare(strict_types=1);

namespace Nexus\Finance\Domain\Contracts;

use DateTimeImmutable;

/**
 * Ledger Repository Interface
 * 
 * Read-only contract for ledger query operations (account balances, trial balance, etc.).
 */
interface LedgerRepositoryInterface
{
    /**
     * Get the balance of an account as of a specific date
     * 
     * @param string $accountId The account ID
     * @param DateTimeImmutable $asOfDate The date to calculate balance
     * @return string The balance amount (string to avoid precision loss)
     */
    public function getAccountBalance(string $accountId, DateTimeImmutable $asOfDate): string;

    /**
     * Get trial balance for all accounts as of a specific date
     * 
     * @return array<array{account_id: string, account_code: string, account_name: string, debit: string, credit: string}>
     */
    public function getTrialBalance(DateTimeImmutable $asOfDate): array;

    /**
     * Get account activity (all transactions) within a date range
     * 
     * @return array<array{date: DateTimeImmutable, entry_number: string, description: string, debit: string, credit: string, balance: string}>
     */
    public function getAccountActivity(string $accountId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array;

    /**
     * Get opening balance for an account at the start of a period
     */
    public function getOpeningBalance(string $accountId, DateTimeImmutable $periodStartDate): string;
}
