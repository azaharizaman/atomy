<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Contracts\Integration;

use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;

/**
 * Interface for accessing asset data from Nexus\Assets package.
 */
interface AssetDataProviderInterface
{
    /**
     * Get asset data by ID.
     *
     * @param string $assetId
     * @return array|null Asset data array or null if not found
     */
    public function getAsset(string $assetId): ?array;

    /**
     * Get asset acquisition cost.
     *
     * @param string $assetId
     * @return float
     */
    public function getAssetCost(string $assetId): float;

    /**
     * Get asset salvage value.
     *
     * @param string $assetId
     * @return float
     */
    public function getAssetSalvageValue(string $assetId): float;

    /**
     * Get asset useful life in months.
     *
     * @param string $assetId
     * @return int
     */
    public function getAssetUsefulLife(string $assetId): int;

    /**
     * Get asset depreciation method.
     *
     * @param string $assetId
     * @return DepreciationMethodType
     */
    public function getAssetDepreciationMethod(string $assetId): DepreciationMethodType;

    /**
     * Get accumulated depreciation for asset.
     *
     * @param string $assetId
     * @return float
     */
    public function getAccumulatedDepreciation(string $assetId): float;

    /**
     * Update accumulated depreciation for asset.
     *
     * @param string $assetId
     * @param float $amount
     * @return void
     */
    public function updateAccumulatedDepreciation(string $assetId, float $amount): void;

    /**
     * Check if asset exists and is active.
     *
     * @param string $assetId
     * @return bool
     */
    public function isAssetActive(string $assetId): bool;

    /**
     * Get asset acquisition date.
     *
     * @param string $assetId
     * @return \DateTimeInterface
     */
    public function getAssetAcquisitionDate(string $assetId): \DateTimeInterface;
}
