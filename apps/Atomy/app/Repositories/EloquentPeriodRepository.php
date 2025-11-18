<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Period;
use DateTimeImmutable;
use Nexus\Period\Contracts\PeriodInterface;
use Nexus\Period\Contracts\PeriodRepositoryInterface;
use Nexus\Period\Enums\PeriodStatus;
use Nexus\Period\Enums\PeriodType;
use Nexus\Period\Exceptions\PeriodHasTransactionsException;

/**
 * Eloquent Period Repository
 * 
 * Implements PeriodRepositoryInterface using Laravel Eloquent.
 */
final class EloquentPeriodRepository implements PeriodRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function find(string $id): ?PeriodInterface
    {
        return Period::find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findOpenByType(PeriodType $type): ?PeriodInterface
    {
        return Period::ofType($type)
            ->open()
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function findByDateRange(DateTimeImmutable $startDate, DateTimeImmutable $endDate, PeriodType $type): array
    {
        $start = $startDate->format('Y-m-d');
        $end = $endDate->format('Y-m-d');
        
        return Period::ofType($type)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                      ->orWhereBetween('end_date', [$start, $end])
                      ->orWhere(function ($q) use ($start, $end) {
                          $q->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                      });
            })
            ->orderBy('start_date')
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function findByDate(DateTimeImmutable $date, PeriodType $type): ?PeriodInterface
    {
        return Period::ofType($type)
            ->containingDate($date)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function findByType(PeriodType $type, ?string $fiscalYear = null): array
    {
        $query = Period::ofType($type);
        
        if ($fiscalYear !== null) {
            $query->forFiscalYear($fiscalYear);
        }
        
        return $query->orderBy('start_date')->get()->all();
    }

    /**
     * {@inheritDoc}
     */
    public function findByStatus(PeriodStatus $status, PeriodType $type): array
    {
        return Period::ofType($type)
            ->withStatus($status)
            ->orderBy('start_date')
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function save(PeriodInterface $period): void
    {
        if ($period instanceof Period) {
            $period->save();
        } else {
            throw new \InvalidArgumentException('Period must be an instance of App\Models\Period');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $id): void
    {
        $transactionCount = $this->getTransactionCount($id);
        
        if ($transactionCount > 0) {
            throw PeriodHasTransactionsException::forPeriod($id, $transactionCount);
        }
        
        Period::destroy($id);
    }

    /**
     * {@inheritDoc}
     */
    public function hasOverlappingPeriod(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        PeriodType $type,
        ?string $excludeId = null
    ): bool {
        $start = $startDate->format('Y-m-d');
        $end = $endDate->format('Y-m-d');
        
        $query = Period::ofType($type)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                      ->orWhereBetween('end_date', [$start, $end])
                      ->orWhere(function ($q) use ($start, $end) {
                          $q->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                      });
            });
        
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getTransactionCount(string $periodId): int
    {
        // This method would check various transaction tables
        // For now, return 0 as a placeholder
        // TODO: Implement actual transaction counting across relevant tables
        return 0;
    }
}
