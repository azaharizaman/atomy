<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Contracts;

use Nexus\JournalEntry\Enums\JournalEntryStatus;

/**
 * Journal Entry Persist Interface (CQRS Write).
 *
 * Provides write operations for journal entry persistence.
 * Implementations handle database transactions, validation hooks, and tenant scoping.
 */
interface JournalEntryPersistInterface
{
    /**
     * Create a new journal entry from data array.
     *
     * @param array<string, mixed> $data Entry data including lines
     * @return JournalEntryInterface The created entry
     */
    public function create(array $data): JournalEntryInterface;

    /**
     * Save a journal entry (create or update).
     *
     * @param JournalEntryInterface $entry The entry to persist
     * @return JournalEntryInterface The persisted entry with any generated fields
     */
    public function save(JournalEntryInterface $entry): JournalEntryInterface;

    /**
     * Delete a draft journal entry.
     *
     * Only draft entries can be deleted. Posted entries must be reversed.
     *
     * @param string $id Journal entry ULID
     * @return void
     * @throws \Nexus\JournalEntry\Exceptions\JournalEntryAlreadyPostedException If entry is posted
     */
    public function delete(string $id): void;

    /**
     * Update journal entry status.
     *
     * @param string $id Journal entry ULID
     * @param JournalEntryStatus $status New status
     * @return JournalEntryInterface Updated entry
     */
    public function updateStatus(string $id, JournalEntryStatus $status): JournalEntryInterface;

    /**
     * Update journal entry status to Posted.
     *
     * @param string $id Journal entry ULID
     * @param string $postedBy User ID who posted
     * @param \DateTimeImmutable $postedAt Posting timestamp
     * @param string|null $periodId Fiscal period ID
     * @return JournalEntryInterface Updated entry
     */
    public function markAsPosted(
        string $id,
        string $postedBy,
        \DateTimeImmutable $postedAt,
        ?string $periodId = null
    ): JournalEntryInterface;

    /**
     * Update journal entry status to Reversed.
     *
     * @param string $id Original entry ULID
     * @param string $reversedById Reversal entry ULID
     * @return JournalEntryInterface Updated entry
     */
    public function markAsReversed(string $id, string $reversedById): JournalEntryInterface;
}
