<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Contracts;

use Nexus\FixedAssetDepreciation\Entities\DepreciationSchedule;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\ProrateConvention;
use Nexus\FixedAssetDepreciation\Exceptions\AssetNotDepreciableException;
use Nexus\FixedAssetDepreciation\Exceptions\DepreciationCalculationException;
use Nexus\FixedAssetDepreciation\Exceptions\ScheduleNotFoundException;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationLife;

/**
 * Interface for depreciation schedule management operations.
 *
 * This interface defines all operations related to creating, adjusting,
 * and managing depreciation schedules for fixed assets. A depreciation
 * schedule represents the complete depreciation plan from acquisition
 * to the end of useful life.
 *
 * @package Nexus\FixedAssetDepreciation\Contracts
 */
interface DepreciationScheduleManagerInterface
{
    /**
     * Generate a new depreciation schedule for an asset.
     *
     * Creates a complete depreciation schedule from the asset's acquisition
     * date through the end of its useful life. The schedule includes all
     * periodic depreciation amounts based on the selected method.
     *
     * @param string $assetId The unique identifier of the asset
     * @param array $options Optional parameters including:
     *                       - method: DepreciationMethodType to override asset's default method
     *                       - startDate: DateTimeImmutable to override start date
     *                       - prorateConvention: ProrateConvention to use for mid-period acquisitions
     *                       - usefulLifeMonths: int to override asset's useful life
     *                       - salvageValue: float to override asset's salvage value
     * @return DepreciationSchedule The generated depreciation schedule
     * @throws AssetNotDepreciableException If the asset cannot be depreciated
     * @throws DepreciationCalculationException If calculation fails
     */
    public function generate(string $assetId, array $options = []): DepreciationSchedule;

    /**
     * Adjust an existing depreciation schedule.
     *
     * Modifies the depreciation schedule when there are changes to the asset's
     * useful life, salvage value, or depreciation method. This will recalculate
     * future depreciation amounts while preserving historical calculations.
     *
     * @param string $scheduleId The unique identifier of the schedule to adjust
     * @param array $adjustments The adjustments to apply:
     *                           - usefulLifeMonths: int New useful life in months
     *                           - salvageValue: float New salvage value
     *                           - method: DepreciationMethodType New depreciation method
     *                           - reason: string Reason for the adjustment
     * @return DepreciationSchedule The adjusted depreciation schedule
     * @throws ScheduleNotFoundException If the schedule does not exist
     * @throws DepreciationCalculationException If recalculation fails
     */
    public function adjust(string $scheduleId, array $adjustments): DepreciationSchedule;

    /**
     * Close a depreciation schedule.
     *
     * Marks the schedule as closed when the asset is fully depreciated,
     * disposed, or no longer requires depreciation tracking.
     *
     * @param string $scheduleId The unique identifier of the schedule to close
     * @param string $reason The reason for closing the schedule
     * @return void
     * @throws ScheduleNotFoundException If the schedule does not exist
     */
    public function close(string $scheduleId, string $reason): void;

    /**
     * Reactivate a closed depreciation schedule.
     *
     * Reopens a previously closed schedule for continued depreciation.
     * This is typically used when an asset's useful life is extended
     * or when a disposal is reversed.
     *
     * @param string $scheduleId The unique identifier of the schedule to reactivate
     * @param string $reason The reason for reactivating the schedule
     * @return DepreciationSchedule The reactivated schedule
     * @throws ScheduleNotFoundException If the schedule does not exist
     */
    public function reactivate(string $scheduleId, string $reason): DepreciationSchedule;

    /**
     * Get a depreciation schedule by its identifier.
     *
     * @param string $scheduleId The unique identifier of the schedule
     * @return DepreciationSchedule|null The schedule or null if not found
     */
    public function getById(string $scheduleId): ?DepreciationSchedule;

    /**
     * Get the active depreciation schedule for an asset.
     *
     * Retrieves the currently active depreciation schedule for the
     * specified asset. An asset typically has only one active schedule
     * at a time.
     *
     * @param string $assetId The unique identifier of the asset
     * @return DepreciationSchedule|null The active schedule or null if none exists
     */
    public function getActiveScheduleForAsset(string $assetId): ?DepreciationSchedule;

    /**
     * Recalculate a schedule from a specific period.
     *
     * Recalculates all depreciation amounts from the specified period
     * onward. This is used when adjustments need to be applied
     * prospectively without changing historical periods.
     *
     * @param string $scheduleId The unique identifier of the schedule
     * @param string $fromPeriodId The period from which to recalculate
     * @param array $newParameters New parameters for the recalculation
     * @return DepreciationSchedule The recalculated schedule
     * @throws ScheduleNotFoundException If the schedule does not exist
     * @throws DepreciationCalculationException If recalculation fails
     */
    public function recalculateFromPeriod(
        string $scheduleId,
        string $fromPeriodId,
        array $newParameters = []
    ): DepreciationSchedule;

    /**
     * Validate schedule parameters before generation or adjustment.
     *
     * Performs validation on the proposed schedule parameters to ensure
     * they are valid and will result in a proper depreciation schedule.
     *
     * @param string $assetId The asset identifier
     * @param DepreciationMethodType $method The depreciation method
     * @param DepreciationLife $life The depreciation life parameters
     * @param ProrateConvention $convention The prorate convention
     * @return array Array of validation errors, empty if valid
     */
    public function validateParameters(
        string $assetId,
        DepreciationMethodType $method,
        DepreciationLife $life,
        ProrateConvention $convention
    ): array;

    /**
     * Check if a schedule can be adjusted.
     *
     * Determines whether a schedule is in a state that allows adjustments.
     * Schedules with posted depreciation in closed periods may have
     * restrictions on certain types of adjustments.
     *
     * @param string $scheduleId The unique identifier of the schedule
     * @return bool True if the schedule can be adjusted
     */
    public function canAdjust(string $scheduleId): bool;

    /**
     * Get schedule adjustment history.
     *
     * Retrieves the history of all adjustments made to a schedule,
     * including the date, reason, and parameters changed.
     *
     * @param string $scheduleId The unique identifier of the schedule
     * @return array Array of adjustment records
     */
    public function getAdjustmentHistory(string $scheduleId): array;
}
