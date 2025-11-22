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
     * Get the fiscal year start month (1-12)
     * 
     * Returns the month number (1 for January, 12 for December) when the fiscal year begins.
     * This is configurable per implementation and defaults to 1 (January = calendar year).
     * 
     * @return int Month number (1-12)
     */
    public function getFiscalYearStartMonth(): int;

    /**
     * Get the period that contains a specific date (alias for getCurrentPeriodForDate)
     * 
     * Convenience method for Finance integration to find which period a transaction belongs to.
     * 
     * @param DateTimeImmutable $date The date to check
     * @param PeriodType $type The period type to search within
     * 
     * @return PeriodInterface|null Returns null if no period exists for the date
     */
    public function getPeriodForDate(DateTimeImmutable $date, PeriodType $type): ?PeriodInterface;

    /**
     * Get the fiscal year for a specific date
     * 
     * Determines which fiscal year a date falls into based on the fiscal year start month.
     * For example, if fiscal year starts in July:
     * - 2024-06-30 → FY-2024 (belongs to fiscal year ending in 2024)
     * - 2024-07-01 → FY-2025 (belongs to fiscal year ending in 2025)
     * 
     * @param DateTimeImmutable $date The date to check
     * 
     * @return string Fiscal year (e.g., "2024", "2025")
     */
    public function getFiscalYearForDate(DateTimeImmutable $date): string;

    /**
     * Get the start date of a fiscal year
     * 
     * Returns the first day of the specified fiscal year.
     * For example, if fiscal year starts in July:
     * - getFiscalYearStartDate("2024") → 2023-07-01 (FY-2024 starts July 1, 2023)
     * - getFiscalYearStartDate("2025") → 2024-07-01 (FY-2025 starts July 1, 2024)
     * 
     * @param string $fiscalYear The fiscal year (e.g., "2024")
     * 
     * @return DateTimeImmutable First day of the fiscal year
     */
    public function getFiscalYearStartDate(string $fiscalYear): DateTimeImmutable;
