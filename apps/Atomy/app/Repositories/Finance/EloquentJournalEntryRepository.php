<?php

declare(strict_types=1);

namespace App\Repositories\Finance;

use App\Models\Finance\JournalEntry;
use DateTimeImmutable;
use Nexus\Finance\Contracts\JournalEntryInterface;
use Nexus\Finance\Contracts\JournalEntryRepositoryInterface;
use Nexus\Finance\Enums\JournalEntryStatus;
use Nexus\Finance\Exceptions\JournalEntryAlreadyPostedException;

/**
 * Eloquent Journal Entry Repository
 * 
 * Implements JournalEntryRepositoryInterface using Laravel Eloquent.
 */
final readonly class EloquentJournalEntryRepository implements JournalEntryRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function find(string $id): ?JournalEntryInterface
    {
        return JournalEntry::with('lines')->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByEntryNumber(string $entryNumber): ?JournalEntryInterface
    {
        return JournalEntry::with('lines')
            ->where('entry_number', $entryNumber)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(array $filters = []): array
    {
        $query = JournalEntry::with('lines');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date'])) {
            $query->where('entry_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('entry_date', '<=', $filters['end_date']);
        }

        if (isset($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        return $query->orderBy('entry_date', 'desc')
            ->orderBy('entry_number', 'desc')
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function findByAccount(
        string $accountId,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $endDate = null
    ): array {
        $query = JournalEntry::with('lines')
            ->whereHas('lines', function ($q) use ($accountId) {
                $q->where('account_id', $accountId);
            });

        if ($startDate !== null) {
            $query->where('entry_date', '>=', $startDate->format('Y-m-d'));
        }

        if ($endDate !== null) {
            $query->where('entry_date', '<=', $endDate->format('Y-m-d'));
        }

        return $query->orderBy('entry_date')
            ->orderBy('entry_number')
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function findByDateRange(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): array {
        return JournalEntry::with('lines')
            ->whereBetween('entry_date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ])
            ->orderBy('entry_date')
            ->orderBy('entry_number')
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function save(JournalEntryInterface $entry): void
    {
        if ($entry instanceof JournalEntry) {
            $entry->save();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $id): void
    {
        $entry = JournalEntry::findOrFail($id);

        // Can only delete draft entries
        if ($entry->status !== JournalEntryStatus::Draft) {
            throw JournalEntryAlreadyPostedException::forEntry(
                $entry->getId(),
                $entry->getEntryNumber()
            );
        }

        // Delete lines first (cascade should handle this, but being explicit)
        $entry->lines()->delete();
        
        $entry->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function getNextEntryNumber(DateTimeImmutable $date): string
    {
        $year = $date->format('Y');
        $month = $date->format('m');
        
        // Find the last entry number for this year-month
        $lastEntry = JournalEntry::where('entry_number', 'like', "JE-{$year}-{$month}-%")
            ->orderBy('entry_number', 'desc')
            ->first();

        if ($lastEntry === null) {
            return "JE-{$year}-{$month}-0001";
        }

        // Extract the sequence number and increment
        $parts = explode('-', $lastEntry->entry_number);
        $sequence = (int)end($parts);
        $nextSequence = $sequence + 1;

        return sprintf('JE-%s-%s-%04d', $year, $month, $nextSequence);
    }
}
