<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Contracts;

use Nexus\JournalEntry\Enums\JournalEntryStatus;

/**
 * Journal Entry Query Interface (CQRS Read).
 *
 * Provides read-only operations for retrieving journal entries.
 * Implementations handle database queries, caching, and tenant scoping.
 */
interface JournalEntryQueryInterface
{
    /**
     * Find a journal entry by ID.
     *
     * @param string $id Journal entry ULID
     * @return JournalEntryInterface|null
     */
    public function findById(string $id): ?JournalEntryInterface;

    /**
     * Find a journal entry by number.
     *
     * @param string $number Journal entry number (e.g., "JE-2024-001234")
     * @return JournalEntryInterface|null
     */
    public function findByNumber(string $number): ?JournalEntryInterface;

    /**
     * Find all journal entries matching criteria.
     *
     * @param array<string, mixed> $filters Optional filters:
     *   - status: JournalEntryStatus
     *   - period_id: string
     *   - date_from: DateTimeImmutable
     *   - date_to: DateTimeImmutable
     *   - account_id: string (entries affecting this account)
     *   - source_system: string
     * @return array<JournalEntryInterface>
     */
    public function findAll(array $filters = []): array;

    /**
     * Find journal entries for a specific account.
     *
     * @param string $accountId Account ULID
     * @param \DateTimeImmutable|null $fromDate Optional start date
     * @param \DateTimeImmutable|null $toDate Optional end date
     * @return array<JournalEntryInterface>
     */
    public function findByAccount(
        string $accountId,
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null
    ): array;

    /**
     * Find journal entries by status.
     *
     * @param JournalEntryStatus $status
     * @return array<JournalEntryInterface>
     */
    public function findByStatus(JournalEntryStatus $status): array;

    /**
     * Find journal entries for a fiscal period.
     *
     * @param string $periodId Period ULID
     * @return array<JournalEntryInterface>
     */
    public function findByPeriod(string $periodId): array;

    /**
     * Find journal entries by date range.
     *
     * @param \DateTimeImmutable $fromDate
     * @param \DateTimeImmutable $toDate
     * @param JournalEntryStatus|null $status Optional status filter
     * @return array<JournalEntryInterface>
     */
    public function findByDateRange(
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate,
        ?JournalEntryStatus $status = null
    ): array;

    /**
     * Check if a journal entry number exists.
     *
     * @param string $number Journal entry number
     * @return bool
     */
    public function numberExists(string $number): bool;

    /**
     * Find the reversal entry for a given entry.
     *
     * @param string $entryId Original entry ID
     * @return JournalEntryInterface|null
     */
    public function findReversalEntry(string $entryId): ?JournalEntryInterface;

    /**
     * Get count of entries for an account.
     *
     * @param string $accountId Account ULID
     * @return int
     */
    public function countByAccount(string $accountId): int;
}
