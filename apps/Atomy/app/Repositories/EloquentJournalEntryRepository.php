<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Nexus\Finance\Contracts\JournalEntryInterface;
use Nexus\Finance\Contracts\JournalEntryRepositoryInterface;
use Nexus\Finance\Exceptions\JournalEntryNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent Journal Entry Repository
 * 
 * Implements JournalEntryRepositoryInterface using Laravel Eloquent.
 */
final readonly class EloquentJournalEntryRepository implements JournalEntryRepositoryInterface
{
    public function __construct(
        private JournalEntry $model
    ) {}

    public function findById(string $id): ?JournalEntryInterface
    {
        return $this->model->with('lines')->find($id);
    }

    public function findByNumber(string $entryNumber): ?JournalEntryInterface
    {
        return $this->model->with('lines')->where('entry_number', $entryNumber)->first();
    }

    public function findByStatus(string $status): array
    {
        return $this->model->with('lines')->ofStatus($status)->get()->all();
    }

    public function findByPeriod(string $periodId): array
    {
        return $this->model->with('lines')->inPeriod($periodId)->get()->all();
    }

    public function findByDateRange(string $startDate, string $endDate): array
    {
        return $this->model->with('lines')
            ->betweenDates($startDate, $endDate)
            ->orderBy('entry_date')
            ->get()
            ->all();
    }

    public function findAll(): array
    {
        return $this->model->with('lines')->orderBy('entry_date', 'desc')->get()->all();
    }

    public function save(JournalEntryInterface $entry): void
    {
        if (!$entry instanceof JournalEntry) {
            throw new \InvalidArgumentException('Journal entry must be an Eloquent model');
        }

        DB::transaction(function () use ($entry) {
            $entry->save();
        });
    }

    public function delete(string $id): void
    {
        $entry = $this->model->find($id);
        
        if (!$entry) {
            throw new JournalEntryNotFoundException("Journal entry with ID {$id} not found");
        }

        DB::transaction(function () use ($entry) {
            $entry->lines()->delete();
            $entry->delete();
        });
    }

    public function exists(string $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    public function numberExists(string $entryNumber): bool
    {
        return $this->model->where('entry_number', $entryNumber)->exists();
    }

    public function getNextSequence(string $pattern, ?string $year = null): int
    {
        $query = $this->model->where('entry_number', 'like', $pattern . '%');
        
        if ($year) {
            $query->whereYear('entry_date', $year);
        }
        
        $lastEntry = $query->orderBy('entry_number', 'desc')->first();
        
        if (!$lastEntry) {
            return 1;
        }

        preg_match('/(\d+)$/', $lastEntry->entry_number, $matches);
        
        return isset($matches[1]) ? (int)$matches[1] + 1 : 1;
    }

    public function getReversalChain(string $entryId): array
    {
        $entry = $this->model->find($entryId);
        
        if (!$entry) {
            return [];
        }

        $chain = [$entry];
        
        $current = $entry;
        while ($current->reversal_of_id) {
            $parent = $this->model->find($current->reversal_of_id);
            if (!$parent) {
                break;
            }
            array_unshift($chain, $parent);
            $current = $parent;
        }
        
        $reversals = $this->model->where('reversal_of_id', $entryId)->get();
        foreach ($reversals as $reversal) {
            $chain[] = $reversal;
        }
        
        return $chain;
    }
}
