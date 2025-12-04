<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Contracts;

use Nexus\Common\ValueObjects\Money;

/**
 * Journal Entry Manager Interface.
 *
 * High-level service interface for journal entry operations.
 * This is the primary public API consumed by other packages and orchestrators.
 */
interface JournalEntryManagerInterface
{
    /**
     * Create a new journal entry in Draft status.
     *
     * @param array{
     *     date: \DateTimeImmutable,
     *     description: string,
     *     reference?: string,
     *     source_system?: string,
     *     source_document_id?: string,
     *     metadata?: array<string, mixed>,
     *     lines: array<array{
     *         account_id: string,
     *         debit: string,
     *         credit: string,
     *         currency?: string,
     *         exchange_rate?: string,
     *         description?: string,
     *         cost_center_id?: string,
     *         project_id?: string,
     *         department_id?: string,
     *         metadata?: array<string, mixed>
     *     }>
     * } $data Entry data
     * @param string|null $createdBy User ID (optional)
     * @return JournalEntryInterface Created entry
     * @throws \Nexus\JournalEntry\Exceptions\UnbalancedJournalEntryException If debits != credits
     * @throws \Nexus\JournalEntry\Exceptions\InvalidJournalEntryException If validation fails
     */
    public function createEntry(array $data, ?string $createdBy = null): JournalEntryInterface;

    /**
     * Post a journal entry.
     *
     * Validates the entry, checks fiscal period, and marks as Posted.
     * Posted entries become immutable.
     *
     * @param string $entryId Journal entry ULID
     * @param string|null $postedBy User ID (optional)
     * @return JournalEntryInterface Posted entry
     * @throws \Nexus\JournalEntry\Exceptions\JournalEntryNotFoundException If entry not found
     * @throws \Nexus\JournalEntry\Exceptions\JournalEntryAlreadyPostedException If already posted
     * @throws \Nexus\JournalEntry\Exceptions\UnbalancedJournalEntryException If not balanced
     * @throws \Nexus\JournalEntry\Exceptions\InvalidAccountException If account validation fails
     * @throws \Nexus\JournalEntry\Exceptions\PeriodClosedException If period is closed
     */
    public function postEntry(string $entryId, ?string $postedBy = null): JournalEntryInterface;

    /**
     * Reverse a posted journal entry.
     *
     * Creates an offsetting entry that swaps debits and credits.
     * Marks the original entry as Reversed.
     *
     * @param string $entryId Original entry ULID
     * @param string|null $reason Reversal reason
     * @param \DateTimeImmutable|null $reversalDate Date for reversal (defaults to today)
     * @param string|null $reversedBy User ID (optional)
     * @return JournalEntryInterface Reversal entry
     * @throws \Nexus\JournalEntry\Exceptions\JournalEntryNotFoundException If entry not found
     * @throws \Nexus\JournalEntry\Exceptions\JournalEntryNotPostedException If entry is not posted
     * @throws \Nexus\JournalEntry\Exceptions\JournalEntryAlreadyReversedException If already reversed
     */
    public function reverseEntry(
        string $entryId,
        ?string $reason = null,
        ?\DateTimeImmutable $reversalDate = null,
        ?string $reversedBy = null
    ): JournalEntryInterface;

    /**
     * Delete a draft journal entry.
     *
     * Only draft entries can be deleted.
     *
     * @param string $entryId Journal entry ULID
     * @return void
     * @throws \Nexus\JournalEntry\Exceptions\JournalEntryNotFoundException If entry not found
     * @throws \Nexus\JournalEntry\Exceptions\JournalEntryAlreadyPostedException If already posted
     */
    public function deleteEntry(string $entryId): void;

    /**
     * Find a journal entry by ID.
     *
     * @param string $entryId Journal entry ULID
     * @return JournalEntryInterface
     * @throws \Nexus\JournalEntry\Exceptions\JournalEntryNotFoundException If not found
     */
    public function findById(string $entryId): JournalEntryInterface;

    /**
     * Find a journal entry by number.
     *
     * @param string $number Journal entry number
     * @return JournalEntryInterface
     * @throws \Nexus\JournalEntry\Exceptions\JournalEntryNotFoundException If not found
     */
    public function findByNumber(string $number): JournalEntryInterface;

    /**
     * Get account balance as of a specific date.
     *
     * @param string $accountId Account ULID
     * @param \DateTimeImmutable $asOfDate Balance date
     * @return Money Account balance
     */
    public function getAccountBalance(string $accountId, \DateTimeImmutable $asOfDate): Money;

    /**
     * Generate trial balance as of a date.
     *
     * @param \DateTimeImmutable $asOfDate Balance date
     * @return array{
     *     balances: array<string, array{account_id: string, debit: Money, credit: Money}>,
     *     total_debit: Money,
     *     total_credit: Money,
     *     is_balanced: bool
     * }
     */
    public function generateTrialBalance(\DateTimeImmutable $asOfDate): array;

    /**
     * Check if an account has transactions (used by ChartOfAccount for deletion validation).
     *
     * @param string $accountId Account ULID
     * @return bool
     */
    public function accountHasTransactions(string $accountId): bool;
}
