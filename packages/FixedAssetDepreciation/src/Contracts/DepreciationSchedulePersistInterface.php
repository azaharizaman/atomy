<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Contracts;

use Nexus\FixedAssetDepreciation\Entities\DepreciationSchedule;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\DepreciationStatus;
use Nexus\FixedAssetDepreciation\Enums\DepreciationType;
use Nexus\FixedAssetDepreciation\Enums\ProrateConvention;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationSchedulePeriod;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationLife;

/**
 * Interface for depreciation schedule write operations.
 *
 * This interface defines all write operations for depreciation schedules,
 * including creation, modification, and deletion of schedules and their periods.
 *
 * @package Nexus\FixedAssetDepreciation\Contracts
 */
interface DepreciationSchedulePersistInterface
{
    /**
     * Create a new depreciation schedule.
     *
     * Creates a new depreciation schedule based on asset parameters.
     *
     * @param string $assetId The asset identifier
     * @param DepreciationMethodType $method The depreciation method
     * @param DepreciationType $type The depreciation type (book/tax)
     * @param DepreciationLife $life The depreciation life
     * @param \DateTimeInterface $acquisitionDate The asset acquisition date
     * @param ProrateConvention $prorateConvention The prorate convention
     * @param array $options Additional options
     * @return DepreciationSchedule The created schedule
     */
    public function create(
        string $assetId,
        DepreciationMethodType $method,
        DepreciationType $type,
        DepreciationLife $life,
        \DateTimeInterface $acquisitionDate,
        ProrateConvention $prorateConvention,
        array $options = []
    ): DepreciationSchedule;

    /**
     * Save a depreciation schedule.
     *
     * @param DepreciationSchedule $schedule The schedule to save
     * @return DepreciationSchedule The saved schedule
     */
    public function save(DepreciationSchedule $schedule): DepreciationSchedule;

    /**
     * Update schedule status.
     *
     * Changes the status of a depreciation schedule.
     *
     * @param string $scheduleId The schedule identifier
     * @param DepreciationStatus $status The new status
     * @return DepreciationSchedule The updated schedule
     */
    public function updateStatus(string $scheduleId, DepreciationStatus $status): DepreciationSchedule;

    /**
     * Add periods to a schedule.
     *
     * Adds depreciation periods to a schedule based on the depreciation
     * method and useful life.
     *
     * @param string $scheduleId The schedule identifier
     * @param array<DepreciationSchedulePeriod> $periods The periods to add
     * @return DepreciationSchedule The updated schedule
     */
    public function addPeriods(string $scheduleId, array $periods): DepreciationSchedule;

    /**
     * Update a schedule period.
     *
     * @param string $scheduleId The schedule identifier
     * @param string $periodId The period identifier
     * @param array $updates The updates to apply
     * @return DepreciationSchedulePeriod The updated period
     */
    public function updatePeriod(
        string $scheduleId,
        string $periodId,
        array $updates
    ): DepreciationSchedulePeriod;

    /**
     * Delete a schedule.
     *
     * Removes a depreciation schedule and all its periods.
     * Typically only allowed for schedules with CALCULATED status.
     *
     * @param string $scheduleId The schedule identifier
     * @return bool True if deleted
     */
    public function delete(string $scheduleId): bool;

    /**
     * Close a schedule.
     *
     * Marks a schedule as closed when the asset is fully depreciated
     * or disposed.
     *
     * @param string $scheduleId The schedule identifier
     * @param string $reason The reason for closing
     * @return DepreciationSchedule The closed schedule
     */
    public function close(string $scheduleId, string $reason): DepreciationSchedule;

    /**
     * Reactivate a closed schedule.
     *
     * Reopens a previously closed schedule for continued depreciation.
     *
     * @param string $scheduleId The schedule identifier
     * @param string $reason The reason for reactivating
     * @return DepreciationSchedule The reactivated schedule
     */
    public function reactivate(string $scheduleId, string $reason): DepreciationSchedule;

    /**
     * Adjust schedule parameters.
     *
     * Modifies depreciation parameters (useful life, salvage value,
     * method) and recalculates future periods.
     *
     * @param string $scheduleId The schedule identifier
     * @param array $adjustments The adjustments to apply:
     *                          - usefulLifeMonths: int
     *                          - salvageValue: float
     *                          - method: DepreciationMethodType
     *                          - reason: string
     * @return DepreciationSchedule The adjusted schedule
     */
    public function adjust(string $scheduleId, array $adjustments): DepreciationSchedule;

    /**
     * Recalculate schedule periods.
     *
     * Recalculates all periods in a schedule from a specific point.
     *
     * @param string $scheduleId The schedule identifier
     * @param string|null $fromPeriodId Start recalculation from this period
     * @return DepreciationSchedule The recalculated schedule
     */
    public function recalculate(string $scheduleId, ?string $fromPeriodId = null): DepreciationSchedule;

    /**
     * Add adjustment record.
     *
     * Records an adjustment made to the schedule for audit purposes.
     *
     * @param string $scheduleId The schedule identifier
     * @param string $adjustmentType The type of adjustment
     * @param array $previousValues Previous parameter values
     * @param array $newValues New parameter values
     * @param string $reason The reason for adjustment
     * @param string $adjustedBy Who made the adjustment
     * @return void
     */
    public function recordAdjustment(
        string $scheduleId,
        string $adjustmentType,
        array $previousValues,
        array $newValues,
        string $reason,
        string $adjustedBy
    ): void;

    /**
     * Lock schedule for processing.
     *
     * Prevents concurrent modifications during batch operations.
     *
     * @param string $scheduleId The schedule identifier
     * @param string $processId The process identifier
     * @return bool True if lock acquired
     */
    public function lock(string $scheduleId, string $processId): bool;

    /**
     * Unlock schedule.
     *
     * @param string $scheduleId The schedule identifier
     * @param string $processId The process identifier
     * @return bool True if lock released
     */
    public function unlock(string $scheduleId, string $processId): bool;

    /**
     * Delete all periods from a schedule.
     *
     * Removes all periods from a schedule, typically done before
     * regenerating the schedule.
     *
     * @param string $scheduleId The schedule identifier
     * @return int Number of periods deleted
     */
    public function deleteAllPeriods(string $scheduleId): int;

    /**
     * Check if schedule can be modified.
     *
     * @param string $scheduleId The schedule identifier
     * @param string $operation The intended operation
     * @return bool True if modification is allowed
     */
    public function canModify(string $scheduleId, string $operation): bool;
}
