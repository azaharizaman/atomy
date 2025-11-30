<?php

declare(strict_types=1);

namespace Nexus\Finance\Infrastructure\Persistence;

use DateTimeImmutable;
use Nexus\Finance\Domain\Contracts\JournalEntryInterface;
use Nexus\Finance\Domain\Contracts\JournalEntryRepositoryInterface;
use Nexus\Finance\Domain\Exceptions\JournalEntryAlreadyPostedException;

/**
 * In-Memory Journal Entry Repository
 * 
 * Internal adapter for testing and development purposes.
 * This repository stores journal entries in memory and does not persist data.
 */
final class InMemoryJournalEntryRepository implements JournalEntryRepositoryInterface
{
    /** @var array<string, JournalEntryInterface> */
    private array $entries = [];

    /** @var int Sequence counter for entry numbers */
    private int $sequenceCounter = 0;

    /**
     * {@inheritDoc}
     */
    public function find(string $id): ?JournalEntryInterface
    {
        return $this->entries[$id] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function findByEntryNumber(string $entryNumber): ?JournalEntryInterface
    {
        foreach ($this->entries as $entry) {
            if ($entry->getEntryNumber() === $entryNumber) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(array $filters = []): array
    {
        $result = $this->entries;

        // Apply filters if provided
        if (isset($filters['status'])) {
            $result = array_filter(
                $result,
                fn(JournalEntryInterface $entry) => $entry->getStatus() === $filters['status']
            );
        }

        if (isset($filters['created_by'])) {
            $result = array_filter(
                $result,
                fn(JournalEntryInterface $entry) => $entry->getCreatedBy() === $filters['created_by']
            );
        }

        return array_values($result);
    }

    /**
     * {@inheritDoc}
     */
    public function findByAccount(
        string $accountId,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $endDate = null
    ): array {
        $result = [];

        foreach ($this->entries as $entry) {
            foreach ($entry->getLines() as $line) {
                if ($line->getAccountId() === $accountId) {
                    // Check date range if provided
                    if ($startDate !== null && $entry->getDate() < $startDate) {
                        continue 2;
                    }
                    if ($endDate !== null && $entry->getDate() > $endDate) {
                        continue 2;
                    }

                    $result[] = $entry;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function findByDateRange(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return array_values(
            array_filter(
                $this->entries,
                fn(JournalEntryInterface $entry) => 
                    $entry->getDate() >= $startDate && $entry->getDate() <= $endDate
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function save(JournalEntryInterface $entry): void
    {
        $this->entries[$entry->getId()] = $entry;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $id): void
    {
        $entry = $this->find($id);

        if ($entry !== null && $entry->isPosted()) {
            throw JournalEntryAlreadyPostedException::forEntry($id, $entry->getEntryNumber());
        }

        unset($this->entries[$id]);
    }

    /**
     * {@inheritDoc}
     */
    public function getNextEntryNumber(DateTimeImmutable $date): string
    {
        $this->sequenceCounter++;
        $year = $date->format('Y');
        $sequence = str_pad((string) $this->sequenceCounter, 4, '0', STR_PAD_LEFT);

        return "JE-{$year}-{$sequence}";
    }

    /**
     * Reset the sequence counter (for testing)
     */
    public function resetSequence(): void
    {
        $this->sequenceCounter = 0;
    }

    /**
     * Clear all entries (for testing)
     */
    public function clear(): void
    {
        $this->entries = [];
        $this->sequenceCounter = 0;
    }
}
