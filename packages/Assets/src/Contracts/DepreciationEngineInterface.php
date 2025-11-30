<?php

declare(strict_types=1);

namespace Nexus\Assets\Contracts;

use DateTimeInterface;
use Nexus\Assets\Enums\DepreciationMethod;

/**
 * Depreciation Engine Interface
 *
 * Calculates depreciation for assets using various methods.
 * Tier 1: Straight-Line only
 * Tier 2: Adds Double Declining Balance
 * Tier 3: Adds Units of Production
 */
interface DepreciationEngineInterface
{
    /**
     * Get the depreciation method handled by this engine
     */
    public function getMethod(): DepreciationMethod;

    /**
     * Calculate period depreciation for an asset
     *
     * For Straight-Line and DDB: Uses date range
     * For Units of Production: Use calculateUnits() instead
     *
     * @param AssetInterface $asset Asset to depreciate
     * @param DateTimeInterface $periodStartDate Period start date
     * @param DateTimeInterface $periodEndDate Period end date
     * @return DepreciationRecordInterface Depreciation record
     * @throws \Nexus\Assets\Exceptions\FullyDepreciatedAssetException
     */
    public function calculateDepreciation(
        AssetInterface $asset,
        DateTimeInterface $periodStartDate,
        DateTimeInterface $periodEndDate
    ): DepreciationRecordInterface;

    /**
     * Calculate depreciation based on units consumed (Tier 3 - Units of Production)
     *
     * @param AssetInterface $asset Asset to depreciate
     * @param float $unitsConsumed Units consumed in period
     * @return DepreciationRecordInterface Depreciation record
     * @throws \Nexus\Assets\Exceptions\FullyDepreciatedAssetException
     * @throws \Nexus\Assets\Exceptions\UnsupportedDepreciationMethodException
     */
    public function calculateUnits(AssetInterface $asset, float $unitsConsumed): DepreciationRecordInterface;

    /**
     * Generate complete depreciation schedule for asset life
     *
     * @param AssetInterface $asset Asset
     * @return array<DepreciationRecordInterface> Array of depreciation records by period
     */
    public function generateSchedule(AssetInterface $asset): array;

    /**
     * Calculate remaining depreciable amount
     *
     * @param AssetInterface $asset Asset
     * @return float Remaining depreciable amount
     */
    public function getRemainingDepreciableAmount(AssetInterface $asset): float;

    /**
     * Check if asset is fully depreciated
     *
     * @param AssetInterface $asset Asset
     * @return bool True if net book value equals salvage value
     */
    public function isFullyDepreciated(AssetInterface $asset): bool;
}
