<?php

declare(strict_types=1);

namespace Nexus\Period\Contracts;

use DateTimeImmutable;
use Nexus\Period\Enums\PeriodStatus;
use Nexus\Period\Enums\PeriodType;

/**
 * Period Repository Interface
 * 
 * Persistence contract for period data operations.
 * Implementations must be provided in the application layer.
 */
interface PeriodRepositoryInterface
{
    /**
     * Find a period by its ID
     * 
     * @return PeriodInterface|null Returns null if not found
     */
    public function find(string $id): ?PeriodInterface;

    /**
     * Find the open period for a specific type
     * 
     * @return PeriodInterface|null Returns null if no open period exists
     */
    public function findOpenByType(PeriodType $type): ?PeriodInterface;

    /**
     * Find all periods within a date range for a specific type
     * 
     * @return array<PeriodInterface>
     */
    public function findByDateRange(DateTimeImmutable $startDate, DateTimeImmutable $endDate, PeriodType $type): array;

    /**
     * Find a period that contains a specific date
     * 
     * @return PeriodInterface|null Returns null if no period found
     */
    public function findByDate(DateTimeImmutable $date, PeriodType $type): ?PeriodInterface;

    /**
     * Find all periods for a specific type and optional fiscal year
     * 
     * @return array<PeriodInterface>
     */
    public function findByType(PeriodType $type, ?string $fiscalYear = null): array;

    /**
     * Find periods by status
     * 
     * @return array<PeriodInterface>
     */
    public function findByStatus(PeriodStatus $status, PeriodType $type): array;

    /**
     * Save a period (create or update)
     */
    public function save(PeriodInterface $period): void;

    /**
     * Delete a period (soft delete recommended)
     * 
     * @throws \Nexus\Period\Exceptions\PeriodHasTransactionsException
     */
    public function delete(string $id): void;

    /**
     * Check if any periods overlap with the given date range
     */
    public function hasOverlappingPeriod(DateTimeImmutable $startDate, DateTimeImmutable $endDate, PeriodType $type, ?string $excludeId = null): bool;

    /**
     * Count transactions associated with a period
     * Used to prevent deletion of periods with transactions
     */
    public function getTransactionCount(string $periodId): int;
}
