<?php

declare(strict_types=1);

namespace Nexus\Finance\Contracts;

use DateTimeImmutable;

/**
 * Finance Manager Interface
 * 
 * Main service contract for general ledger and journal entry operations.
 */
interface FinanceManagerInterface
{
    /**
     * Create a new journal entry in draft status
     * 
     * @param array<string, mixed> $data Entry data with lines
     * @return JournalEntryInterface
     * 
     * @throws \Nexus\Finance\Exceptions\InvalidJournalEntryException
     * @throws \Nexus\Finance\Exceptions\UnbalancedJournalEntryException
     */
    public function createJournalEntry(array $data): JournalEntryInterface;

    /**
     * Post a journal entry to the general ledger
     * 
     * @param string $journalEntryId The ID of the entry to post
     * 
     * @throws \Nexus\Finance\Exceptions\JournalEntryNotFoundException
     * @throws \Nexus\Finance\Exceptions\JournalEntryAlreadyPostedException
     * @throws \Nexus\Finance\Exceptions\UnbalancedJournalEntryException
     * @throws \Nexus\Period\Exceptions\PostingPeriodClosedException
     */
    public function postJournalEntry(string $journalEntryId): void;

    /**
     * Reverse a posted journal entry
     * 
     * @param string $journalEntryId The ID of the entry to reverse
     * @param DateTimeImmutable $reversalDate The date for the reversal entry
     * @param string $reason Reason for reversal
     * @return JournalEntryInterface The reversal entry
     * 
     * @throws \Nexus\Finance\Exceptions\JournalEntryNotFoundException
     * @throws \Nexus\Finance\Exceptions\JournalEntryNotPostedException
     */
    public function reverseJournalEntry(string $journalEntryId, DateTimeImmutable $reversalDate, string $reason): JournalEntryInterface;

    /**
     * Get a journal entry by ID
     * 
     * @throws \Nexus\Finance\Exceptions\JournalEntryNotFoundException
     */
    public function findJournalEntry(string $journalEntryId): JournalEntryInterface;

    /**
     * Get an account by ID
     * 
     * @throws \Nexus\Finance\Exceptions\AccountNotFoundException
     */
    public function findAccount(string $accountId): AccountInterface;

    /**
     * Get an account by code
     * 
     * @throws \Nexus\Finance\Exceptions\AccountNotFoundException
     */
    public function findAccountByCode(string $accountCode): AccountInterface;

    /**
     * Create a new account in the chart of accounts
     * 
     * @param array<string, mixed> $data Account data
     * @return AccountInterface
     * 
     * @throws \Nexus\Finance\Exceptions\DuplicateAccountCodeException
     * @throws \Nexus\Finance\Exceptions\InvalidAccountException
     */
    public function createAccount(array $data): AccountInterface;

    /**
     * Get the account balance as of a specific date
     * 
     * @param string $accountId The account ID
     * @param DateTimeImmutable $asOfDate The date to calculate balance
     * @return string The balance amount
     * 
     * @throws \Nexus\Finance\Exceptions\AccountNotFoundException
     */
    public function getAccountBalance(string $accountId, DateTimeImmutable $asOfDate): string;

    /**
     * List all accounts (optionally filtered)
     * 
     * @param array<string, mixed> $filters Optional filters
     * @return array<AccountInterface>
     */
    public function listAccounts(array $filters = []): array;

    /**
     * List journal entries (optionally filtered)
     * 
     * @param array<string, mixed> $filters Optional filters
     * @return array<JournalEntryInterface>
     */
    public function listJournalEntries(array $filters = []): array;
}
