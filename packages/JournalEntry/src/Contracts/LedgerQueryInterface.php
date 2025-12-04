<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Contracts;

use Nexus\Common\ValueObjects\Money;

/**
 * Ledger Query Interface.
 *
 * Provides read-only operations for ledger balance and transaction queries.
 * Used by balance calculation, reporting, and analytics.
 */
interface LedgerQueryInterface
{
    /**
     * Get account balance as of a specific date.
     *
     * Calculates the sum of all posted transactions up to and including the date.
     *
     * @param string $accountId Account ULID
     * @param \DateTimeImmutable $asOfDate Balance date
     * @return Money Account balance
     */
    public function getAccountBalance(
        string $accountId,
        \DateTimeImmutable $asOfDate
    ): Money;

    /**
     * Get account balance for a date range.
     *
     * @param string $accountId Account ULID
     * @param \DateTimeImmutable $fromDate Start date
     * @param \DateTimeImmutable $toDate End date
     * @return Money Net balance for the period
     */
    public function getAccountBalanceForRange(
        string $accountId,
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate
    ): Money;

    /**
     * Get all account balances as of a date (for trial balance).
     *
     * @param \DateTimeImmutable $asOfDate Balance date
     * @param array<string, mixed> $filters Optional filters:
     *   - account_type: AccountType enum
     *   - active_only: bool
     * @return array<string, Money> Map of account ID to balance
     */
    public function getAllAccountBalances(
        \DateTimeImmutable $asOfDate,
        array $filters = []
    ): array;

    /**
     * Get line items for an account (for drill-down).
     *
     * @param string $accountId Account ULID
     * @param \DateTimeImmutable|null $fromDate Optional start date
     * @param \DateTimeImmutable|null $toDate Optional end date
     * @return array<JournalEntryLineInterface>
     */
    public function getAccountLineItems(
        string $accountId,
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null
    ): array;

    /**
     * Get running balance for an account (for account ledger report).
     *
     * Returns line items with running balance.
     *
     * @param string $accountId Account ULID
     * @param \DateTimeImmutable $fromDate Start date
     * @param \DateTimeImmutable $toDate End date
     * @return array<array{line: JournalEntryLineInterface, running_balance: Money}>
     */
    public function getAccountLedgerWithRunningBalance(
        string $accountId,
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate
    ): array;

    /**
     * Check if an account has any posted transactions.
     *
     * @param string $accountId Account ULID
     * @return bool
     */
    public function accountHasTransactions(string $accountId): bool;

    /**
     * Get total debits and credits for an account in a period.
     *
     * @param string $accountId Account ULID
     * @param \DateTimeImmutable $fromDate Start date
     * @param \DateTimeImmutable $toDate End date
     * @return array{debit: Money, credit: Money}
     */
    public function getAccountTotals(
        string $accountId,
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate
    ): array;
}
