<?php

declare(strict_types=1);

namespace Nexus\Period\Contracts;

use DateTimeImmutable;
use Nexus\Period\Enums\PeriodType;

/**
 * Period Manager Interface
 * 
 * Main service contract for period management operations.
 * This is the primary API for period-related operations.
 */
interface PeriodManagerInterface
{
    /**
     * Check if posting is allowed for a specific date and period type
     * 
     * This is a critical performance path - must execute in < 5ms
     * 
     * @throws \Nexus\Period\Exceptions\NoOpenPeriodException if no open period exists
     */
    public function isPostingAllowed(DateTimeImmutable $date, PeriodType $type): bool;

    /**
     * Get the currently open period for a specific type
     * 
     * @return PeriodInterface|null Returns null if no period is open
     */
    public function getOpenPeriod(PeriodType $type): ?PeriodInterface;

    /**
     * Get the period that contains a specific date
     * 
     * @return PeriodInterface|null Returns null if no period exists for the date
     */
    public function getCurrentPeriodForDate(DateTimeImmutable $date, PeriodType $type): ?PeriodInterface;

    /**
     * Close a period with audit reason
     * 
     * @param string $periodId The ID of the period to close
     * @param string $reason The reason for closing the period
     * @param string $userId The user performing the operation
     * 
     * @throws \Nexus\Period\Exceptions\PeriodNotFoundException
     * @throws \Nexus\Period\Exceptions\InvalidPeriodStatusException
     */
    public function closePeriod(string $periodId, string $reason, string $userId): void;

    /**
     * Reopen a closed period (requires authorization)
     * 
     * @param string $periodId The ID of the period to reopen
     * @param string $reason The reason for reopening
     * @param string $userId The user performing the operation
     * 
     * @throws \Nexus\Period\Exceptions\PeriodNotFoundException
     * @throws \Nexus\Period\Exceptions\PeriodReopeningUnauthorizedException
     * @throws \Nexus\Period\Exceptions\InvalidPeriodStatusException
     */
    public function reopenPeriod(string $periodId, string $reason, string $userId): void;

    /**
     * Create the next sequential period for a type
     * 
     * @throws \Nexus\Period\Exceptions\OverlappingPeriodException
     */
    public function createNextPeriod(PeriodType $type): PeriodInterface;

    /**
     * List all periods for a specific type and optional fiscal year
     * 
     * @param PeriodType $type The period type to filter by
     * @param string|null $fiscalYear Optional fiscal year filter
     * 
     * @return array<PeriodInterface>
     */
    public function listPeriods(PeriodType $type, ?string $fiscalYear = null): array;

    /**
     * Find a specific period by ID
     * 
     * @throws \Nexus\Period\Exceptions\PeriodNotFoundException
     */
    public function findById(string $periodId): PeriodInterface;

    /**
     * Lock a period to prevent any further transactions
     * 
     * Transitions the period to Locked status, which is permanent.
     * Locked periods cannot be reopened.
     * 
     * @param string $periodId The ID of the period to lock
     * @param string $reason The reason for locking
     * @param string $userId The user performing the operation
     * 
     * @throws \Nexus\Period\Exceptions\PeriodNotFoundException
     * @throws \Nexus\Period\Exceptions\InvalidPeriodStatusException
     */
    public function lockPeriod(string $periodId, string $reason, string $userId): void;

    /**
     * Unlock a previously locked period (requires special authorization)
     * 
     * This is a highly restricted operation that should only be allowed
     * in exceptional circumstances (e.g., audit corrections).
     * 
     * @param string $periodId The ID of the period to unlock
     * @param string $reason The reason for unlocking
     * @param string $userId The user performing the operation
     * 
     * @throws \Nexus\Period\Exceptions\PeriodNotFoundException
     * @throws \Nexus\Period\Exceptions\PeriodUnlockingUnauthorizedException
     * @throws \Nexus\Period\Exceptions\InvalidPeriodStatusException
     */
    public function unlockPeriod(string $periodId, string $reason, string $userId): void;

    /**
     * Lock all periods within a fiscal year
     * 
     * This is typically done at year-end after all closing procedures are complete.
     * 
     * @param string $fiscalYear The fiscal year to lock (e.g., "2024")
     * @param PeriodType $type The period type to lock
     * @param string $reason The reason for locking
     * @param string $userId The user performing the operation
     * 
     * @return int Number of periods locked
     */
    public function lockFiscalYear(string $fiscalYear, PeriodType $type, string $reason, string $userId): int;

    /**
     * Get all periods for a specific fiscal year
     * 
     * @param string $fiscalYear The fiscal year (e.g., "2024")
     * @param PeriodType $type The period type
     * 
     * @return array<PeriodInterface> List of periods in the fiscal year
     */
    public function getPeriodsForFiscalYear(string $fiscalYear, PeriodType $type): array;
}
