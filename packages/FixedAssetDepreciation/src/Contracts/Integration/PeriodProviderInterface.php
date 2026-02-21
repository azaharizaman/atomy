<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Contracts\Integration;

/**
 * Interface for fiscal period integration.
 *
 * This interface defines the contract for integrating with the
 * Nexus\Period package to validate fiscal periods for
 * depreciation calculations.
 *
 * @package Nexus\FixedAssetDepreciation\Contracts\Integration
 */
interface PeriodProviderInterface
{
    /**
     * Get current fiscal period.
     *
     * Returns the currently active fiscal period.
     *
     * @return array{
     *     id: string,
     *     periodNumber: int,
     *     fiscalYear: int,
     *     startDate: \DateTimeInterface,
     *     endDate: \DateTimeInterface,
     *     status: string
     * }|null The current period or null
     */
    public function getCurrentPeriod(): ?array;

    /**
     * Get fiscal period by ID.
     *
     * @param string $periodId The period identifier
     * @return array|null The period data or null
     */
    public function getPeriod(string $periodId): ?array;

    /**
     * Get fiscal period for date.
     *
     * Returns the fiscal period that contains the given date.
     *
     * @param \DateTimeInterface $date The date to look up
     * @return array|null The period data or null
     */
    public function getPeriodForDate(\DateTimeInterface $date): ?array;

    /**
     * Get period by year and number.
     *
     * @param int $fiscalYear The fiscal year
     * @param int $periodNumber The period number
     * @return array|null The period data or null
     */
    public function getPeriodByYearAndNumber(int $fiscalYear, int $periodNumber): ?array;

    /**
     * Check if period is open.
     *
     * @param string $periodId The period identifier
     * @return bool True if the period is open for transactions
     */
    public function isPeriodOpen(string $periodId): bool;

    /**
     * Check if period is closed.
     *
     * @param string $periodId The period identifier
     * @return bool True if the period is closed
     */
    public function isPeriodClosed(string $periodId): bool;

    /**
     * Check if period is locked.
     *
     * @param string $periodId The period identifier
     * @return bool True if the period is locked
     */
    public function isPeriodLocked(string $periodId): bool;

    /**
     * Get periods in range.
     *
     * Returns all fiscal periods within a date range.
     *
     * @param \DateTimeInterface $startDate The start date
     * @param \DateTimeInterface $endDate The end date
     * @return array Array of period data
     */
    public function getPeriodsInRange(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array;

    /**
     * Get periods for fiscal year.
     *
     * Returns all periods for a fiscal year.
     *
     * @param int $fiscalYear The fiscal year
     * @return array Array of period data
     */
    public function getPeriodsForYear(int $fiscalYear): array;

    /**
     * Get previous period.
     *
     * Returns the period immediately before the given period.
     *
     * @param string $periodId The period identifier
     * @return array|null The previous period or null
     */
    public function getPreviousPeriod(string $periodId): ?array;

    /**
     * Get next period.
     *
     * Returns the period immediately after the given period.
     *
     * @param string $periodId The period identifier
     * @return array|null The next period or null
     */
    public function getNextPeriod(string $periodId): ?array;

    /**
     * Validate period for depreciation.
     *
     * Checks if the period is valid for depreciation posting,
     * considering open/closed status and any depreciation-specific
     * rules.
     *
     * @param string $periodId The period identifier
     * @return array{valid: bool, errors: array<string>} Validation result
     */
    public function validateForDepreciation(string $periodId): array;

    /**
     * Get year end period.
     *
     * Returns the period that contains the fiscal year end date.
     *
     * @param int $fiscalYear The fiscal year
     * @return array|null The year end period or null
     */
    public function getYearEndPeriod(int $fiscalYear): ?array;

    /**
     * Get fiscal year for date.
     *
     * @param \DateTimeInterface $date The date to look up
     * @return int|null The fiscal year or null
     */
    public function getFiscalYearForDate(\DateTimeInterface $date): ?int;

    /**
     * Check if date falls in current period.
     *
     * @param \DateTimeInterface $date The date to check
     * @return bool True if date is in the current period
     */
    public function isDateInCurrentPeriod(\DateTimeInterface $date): bool;
}
