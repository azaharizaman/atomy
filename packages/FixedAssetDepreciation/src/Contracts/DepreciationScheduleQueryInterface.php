<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Contracts;

use Nexus\FixedAssetDepreciation\Entities\DepreciationSchedule;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\DepreciationStatus;
use Nexus\FixedAssetDepreciation\Enums\DepreciationType;
use Nexus\FixedAssetDepreciation\Enums\ProrateConvention;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationSchedulePeriod;

/**
 * Interface for depreciation schedule query operations.
 *
 * This interface defines all read-only query operations specific to
 * depreciation schedules. It provides methods for retrieving schedule
 * information, periods, and related data.
 *
 * @package Nexus\FixedAssetDepreciation\Contracts
 */
interface DepreciationScheduleQueryInterface
{
    /**
     * Get a depreciation schedule by ID.
     *
     * @param string $scheduleId The schedule identifier
     * @return DepreciationSchedule|null The schedule or null if not found
     */
    public function getById(string $scheduleId): ?DepreciationSchedule;

    /**
     * Get the active schedule for an asset.
     *
     * Returns the currently active depreciation schedule for the asset.
     *
     * @param string $assetId The asset identifier
     * @return DepreciationSchedule|null The active schedule or null
     */
    public function getActiveForAsset(string $assetId): ?DepreciationSchedule;

    /**
     * Get all schedules for an asset.
     *
     * Returns all depreciation schedules associated with an asset,
     * including closed and historical schedules.
     *
     * @param string $assetId The asset identifier
     * @return array<DepreciationSchedule> Array of schedules
     */
    public function getAllForAsset(string $assetId): array;

    /**
     * Get schedule periods.
     *
     * Returns all periods in a depreciation schedule, optionally
     * filtered by date range or status.
     *
     * @param string $scheduleId The schedule identifier
     * @param array $filters Optional filters:
     *                       - startDate: \DateTimeInterface earliest period
     *                       - endDate: \DateTimeInterface latest period
     *                       - status: DepreciationStatus filter
     *                       - includeReversed: bool include reversed periods
     * @return array<DepreciationSchedulePeriod> Array of schedule periods
     */
    public function getPeriods(string $scheduleId, array $filters = []): array;

    /**
     * Get a specific schedule period.
     *
     * @param string $scheduleId The schedule identifier
     * @param string $periodId The period identifier
     * @return DepreciationSchedulePeriod|null The period or null
     */
    public function getPeriod(string $scheduleId, string $periodId): ?DepreciationSchedulePeriod;

    /**
     * Get current period of a schedule.
     *
     * Returns the period that contains the current date or the
     * most recent completed period.
     *
     * @param string $scheduleId The schedule identifier
     * @return DepreciationSchedulePeriod|null The current period or null
     */
    public function getCurrentPeriod(string $scheduleId): ?DepreciationSchedulePeriod;

    /**
     * Get remaining periods in a schedule.
     *
     * Returns all future periods from a given date, useful for
     * forecasting and reporting.
     *
     * @param string $scheduleId The schedule identifier
     * @param \DateTimeInterface|null $fromDate Starting date for remaining periods
     * @return array<DepreciationSchedulePeriod> Array of remaining periods
     */
    public function getRemainingPeriods(string $scheduleId, ?\DateTimeInterface $fromDate = null): array;

    /**
     * Get completed periods in a schedule.
     *
     * Returns all periods that have been fully processed (posted or calculated).
     *
     * @param string $scheduleId The schedule identifier
     * @return array<DepreciationSchedulePeriod> Array of completed periods
     */
    public function getCompletedPeriods(string $scheduleId): array;

    /**
     * Get schedule status.
     *
     * @param string $scheduleId The schedule identifier
     * @return DepreciationStatus|null The schedule status or null
     */
    public function getStatus(string $scheduleId): ?DepreciationStatus;

    /**
     * Check if schedule is active.
     *
     * @param string $scheduleId The schedule identifier
     * @return bool True if the schedule is active
     */
    public function isActive(string $scheduleId): bool;

    /**
     * Check if schedule is fully depreciated.
     *
     * @param string $scheduleId The schedule identifier
     * @return bool True if fully depreciated
     */
    public function isFullyDepreciated(string $scheduleId): bool;

    /**
     * Get depreciation method for a schedule.
     *
     * @param string $scheduleId The schedule identifier
     * @return DepreciationMethodType|null The depreciation method
     */
    public function getMethod(string $scheduleId): ?DepreciationMethodType;

    /**
     * Get depreciation type for a schedule.
     *
     * @param string $scheduleId The schedule identifier
     * @return DepreciationType|null The depreciation type (book/tax)
     */
    public function getDepreciationType(string $scheduleId): ?DepreciationType;

    /**
     * Get prorate convention for a schedule.
     *
     * @param string $scheduleId The schedule identifier
     * @return ProrateConvention|null The prorate convention
     */
    public function getProrateConvention(string $scheduleId): ?ProrateConvention;

    /**
     * Get schedule dates.
     *
     * Returns key dates associated with the schedule.
     *
     * @param string $scheduleId The schedule identifier
     * @return array{
     *     acquisitionDate: \DateTimeInterface|null,
     *     startDate: \DateTimeInterface|null,
     *     endDate: \DateTimeInterface|null,
     *     closedDate: \DateTimeInterface|null
     * }
     */
    public function getDates(string $scheduleId): array;

    /**
     * Get total depreciation amount for a schedule.
     *
     * @param string $scheduleId The schedule identifier
     * @return float The total depreciation amount
     */
    public function getTotalDepreciation(string $scheduleId): float;

    /**
     * Get remaining depreciation amount for a schedule.
     *
     * @param string $scheduleId The schedule identifier
     * @param \DateTimeInterface|null $asOfDate Date to calculate from
     * @return float The remaining depreciation amount
     */
    public function getRemainingDepreciationAmount(string $scheduleId, ?\DateTimeInterface $asOfDate = null): float;

    /**
     * Search schedules with criteria.
     *
     * @param array $criteria Search criteria:
     *                       - status: DepreciationStatus
     *                       - method: DepreciationMethodType
     *                       - type: DepreciationType
     *                       - assetId: string
     *                       - startDateFrom: \DateTimeInterface
     *                       - startDateTo: \DateTimeInterface
     *                       - isActive: bool
     * @param array $pagination Pagination: limit, offset, sortBy, sortOrder
     * @return array<DepreciationSchedule> Matching schedules
     */
    public function search(array $criteria = [], array $pagination = []): array;

    /**
     * Get schedules by status.
     *
     * @param DepreciationStatus $status The status to filter by
     * @param array $pagination Pagination options
     * @return array<DepreciationSchedule> Array of schedules
     */
    public function getByStatus(DepreciationStatus $status, array $pagination = []): array;

    /**
     * Get count of schedules.
     *
     * @param array $filters Optional filters
     * @return int Total count
     */
    public function getCount(array $filters = []): int;

    /**
     * Get schedules ready for period close.
     *
     * Returns schedules that have depreciation calculated but
     * not yet posted for a specific period.
     *
     * @param string $periodId The period identifier
     * @return array<DepreciationSchedule> Array of schedules ready for close
     */
    public function getReadyForPeriodClose(string $periodId): array;
}
