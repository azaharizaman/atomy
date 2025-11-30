<?php

declare(strict_types=1);

namespace Nexus\Laravel\Finance\Repositories;

use DateTimeImmutable;
use Nexus\Finance\Domain\Contracts\JournalEntryInterface;
use Nexus\Finance\Domain\Contracts\JournalEntryRepositoryInterface;
use Nexus\Finance\Domain\Exceptions\JournalEntryAlreadyPostedException;
use Nexus\Laravel\Finance\Models\JournalEntry;

/**
 * Eloquent implementation of Journal Entry Repository
 */
final readonly class EloquentJournalEntryRepository implements JournalEntryRepositoryInterface
{
    public function find(string $id): ?JournalEntryInterface
    {
        return JournalEntry::with('lines')->find($id);
    }

    public function findByEntryNumber(string $entryNumber): ?JournalEntryInterface
    {
        return JournalEntry::with('lines')
            ->where('entry_number', $entryNumber)
            ->first();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<JournalEntryInterface>
     */
    public function findAll(array $filters = []): array
    {
        $query = JournalEntry::with('lines');
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['start_date'])) {
            $query->where('date', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->where('date', '<=', $filters['end_date']);
        }
        
        if (isset($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }
        
        return $query->orderByDesc('date')
            ->orderByDesc('entry_number')
            ->get()
            ->all();
    }

    /**
     * @return array<JournalEntryInterface>
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
            $query->where('date', '>=', $startDate->format('Y-m-d'));
        }
        
        if ($endDate !== null) {
            $query->where('date', '<=', $endDate->format('Y-m-d'));
        }
        
        return $query->orderByDesc('date')
            ->orderByDesc('entry_number')
            ->get()
            ->all();
    }

    /**
     * @return array<JournalEntryInterface>
     */
    public function findByDateRange(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return JournalEntry::with('lines')
            ->whereBetween('date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ])
            ->orderByDesc('date')
            ->orderByDesc('entry_number')
            ->get()
            ->all();
    }

    public function save(JournalEntryInterface $entry): void
    {
        if ($entry instanceof JournalEntry) {
            $entry->save();
            return;
        }

        // Handle case where it's not an Eloquent model
        $journalEntry = JournalEntry::updateOrCreate(
            ['id' => $entry->getId()],
            [
                'entry_number' => $entry->getEntryNumber(),
                'date' => $entry->getDate()->format('Y-m-d'),
                'reference' => $entry->getReference(),
                'description' => $entry->getDescription(),
                'status' => $entry->getStatus(),
                'created_by' => $entry->getCreatedBy(),
                'posted_at' => $entry->getPostedAt()?->format('Y-m-d H:i:s'),
            ]
        );

        // Sync lines by replacing all lines.
        // This approach is simpler and ensures consistency when receiving
        // a non-Eloquent JournalEntryInterface. For Eloquent models,
        // we use the direct save() path above which is more efficient.
        $journalEntry->lines()->delete();
        foreach ($entry->getLines() as $line) {
            $journalEntry->lines()->create([
                'id' => $line->getId(),
                'account_id' => $line->getAccountId(),
                'debit_amount' => $line->getDebitAmount()->getAmount(),
                'credit_amount' => $line->getCreditAmount()->getAmount(),
                'currency' => $line->getDebitAmount()->getCurrency(),
                'description' => $line->getDescription(),
            ]);
        }
    }

    public function delete(string $id): void
    {
        $entry = JournalEntry::find($id);
        
        if ($entry === null) {
            return;
        }
        
        if ($entry->isPosted()) {
            throw new JournalEntryAlreadyPostedException(
                "Cannot delete posted journal entry: {$id}"
            );
        }
        
        // Delete lines first due to foreign key constraints
        $entry->lines()->delete();
        $entry->delete();
    }

    public function getNextEntryNumber(DateTimeImmutable $date): string
    {
        $year = $date->format('Y');
        $prefix = "JE-{$year}-";
        
        $lastEntry = JournalEntry::where('entry_number', 'like', $prefix . '%')
            ->orderByDesc('entry_number')
            ->first();
        
        if ($lastEntry === null) {
            return $prefix . '00001';
        }
        
        $lastNumber = (int) str_replace($prefix, '', $lastEntry->entry_number);
        return $prefix . str_pad((string) ($lastNumber + 1), 5, '0', STR_PAD_LEFT);
    }
}
