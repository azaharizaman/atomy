<?php

declare(strict_types=1);

namespace Nexus\Finance\Domain\Contracts;

use DateTimeImmutable;

/**
 * Journal Entry Repository Interface
 * 
 * Persistence contract for journal entry operations.
 */
interface JournalEntryRepositoryInterface
{
    /**
     * Find a journal entry by ID
     */
    public function find(string $id): ?JournalEntryInterface;

    /**
     * Find a journal entry by entry number
     */
    public function findByEntryNumber(string $entryNumber): ?JournalEntryInterface;

    /**
     * Find all journal entries (optionally filtered)
     * 
     * @param array<string, mixed> $filters
     * @return array<JournalEntryInterface>
     */
    public function findAll(array $filters = []): array;

    /**
     * Find journal entries for a specific account
     * 
     * @return array<JournalEntryInterface>
     */
    public function findByAccount(string $accountId, ?DateTimeImmutable $startDate = null, ?DateTimeImmutable $endDate = null): array;

    /**
     * Find journal entries within a date range
     * 
     * @return array<JournalEntryInterface>
     */
    public function findByDateRange(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array;

    /**
     * Save a journal entry (create or update)
     */
    public function save(JournalEntryInterface $entry): void;

    /**
     * Delete a journal entry (only if not posted)
     * 
     * @throws \Nexus\Finance\Domain\Exceptions\JournalEntryAlreadyPostedException
     */
    public function delete(string $id): void;

    /**
     * Get the next entry number for a given date
     */
    public function getNextEntryNumber(DateTimeImmutable $date): string;
}
