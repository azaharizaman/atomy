<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Contracts;

use Nexus\GeneralLedger\Entities\Transaction;
use Nexus\GeneralLedger\ValueObjects\AccountBalance;

/**
 * Transaction Query Interface
 * 
 * Read-only operations for querying transaction data.
 */
interface TransactionQueryInterface
{
    /**
     * Find a transaction by its ID
     * 
     * @param string $id Transaction ULID
     * @return Transaction|null The transaction if found
     */
    public function findById(string $id): ?Transaction;

    /**
     * Find all transactions for an account
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @return array<Transaction> Transactions for the account
     */
    public function findByAccount(string $ledgerAccountId): array;

    /**
     * Find transactions for a period
     * 
     * @param string $ledgerId Ledger ULID
     * @param string $periodId Period ULID
     * @return array<Transaction> Transactions in the period
     */
    public function findByPeriod(string $ledgerId, string $periodId): array;

    /**
     * Find transactions by journal entry line
     * 
     * @param string $journalEntryLineId Journal entry line ULID
     * @return Transaction|null The transaction if found
     */
    public function findByJournalEntryLine(string $journalEntryLineId): ?Transaction;

    /**
     * Find transactions within a date range
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @param \DateTimeImmutable $fromDate Start date
     * @param \DateTimeImmutable $toDate End date
     * @return array<Transaction> Transactions in the range
     */
    public function findByDateRange(
        string $ledgerAccountId,
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate,
    ): array;

    /**
     * Get account balance as of a date
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @param \DateTimeImmutable $asOfDate Balance as of date
     * @return AccountBalance Current balance
     */
    public function getAccountBalance(string $ledgerAccountId, \DateTimeImmutable $asOfDate): AccountBalance;

    /**
     * Get account balance for a period
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @param string $periodId Period ULID
     * @return AccountBalance Balance for the period
     */
    public function getAccountBalanceForPeriod(string $ledgerAccountId, string $periodId): AccountBalance;

    /**
     * Check if account has any transactions
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @return bool True if account has transactions
     */
    public function accountHasTransactions(string $ledgerAccountId): bool;

    /**
     * Get total debits for an account in a period
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @param string $periodId Period ULID
     * @return AccountBalance Total debits
     */
    public function getTotalDebits(string $ledgerAccountId, string $periodId): AccountBalance;

    /**
     * Get total credits for an account in a period
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @param string $periodId Period ULID
     * @return AccountBalance Total credits
     */
    public function getTotalCredits(string $ledgerAccountId, string $periodId): AccountBalance;

    /**
     * Get the last transaction for an account
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @return Transaction|null The last transaction
     */
    public function getLastTransaction(string $ledgerAccountId): ?Transaction;

    /**
     * Count transactions for an account
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @return int Number of transactions
     */
    public function countByAccount(string $ledgerAccountId): int;
}
