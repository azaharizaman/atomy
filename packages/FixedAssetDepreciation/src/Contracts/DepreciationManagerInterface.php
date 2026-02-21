<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Contracts;

/**
 * Primary facade interface for depreciation operations.
 *
 * This is the main entry point for all depreciation-related operations
 * in the FixedAssetDepreciation package.
 */
interface DepreciationManagerInterface
{
    /**
     * Calculate depreciation for a single asset in a specific period.
     *
     * @param string $assetId The asset identifier
     * @param string $periodId The fiscal period identifier
     * @return \Nexus\FixedAssetDepreciation\Entities\AssetDepreciation
     * @throws \Nexus\FixedAssetDepreciation\Exceptions\DepreciationCalculationException
     * @throws \Nexus\FixedAssetDepreciation\Exceptions\AssetNotDepreciableException
     */
    public function calculateDepreciation(string $assetId, string $periodId): \Nexus\FixedAssetDepreciation\Entities\AssetDepreciation;

    /**
     * Generate full depreciation schedule for an asset.
     *
     * @param string $assetId The asset identifier
     * @return \Nexus\FixedAssetDepreciation\Entities\DepreciationSchedule
     * @throws \Nexus\FixedAssetDepreciation\Exceptions\DepreciationCalculationException
     */
    public function generateSchedule(string $assetId): \Nexus\FixedAssetDepreciation\Entities\DepreciationSchedule;

    /**
     * Run batch depreciation for all assets in a period.
     *
     * @param string $periodId The fiscal period identifier
     * @return \Nexus\FixedAssetDepreciation\ValueObjects\DepreciationRunResult
     * @throws \Nexus\FixedAssetDepreciation\Exceptions\DepreciationPeriodClosedException
     */
    public function runPeriodicDepreciation(string $periodId): \Nexus\FixedAssetDepreciation\ValueObjects\DepreciationRunResult;

    /**
     * Reverse a depreciation calculation.
     *
     * @param string $depreciationId The depreciation identifier to reverse
     * @param string $reason The reason for reversal
     * @return void
     * @throws \Nexus\FixedAssetDepreciation\Exceptions\DepreciationException
     */
    public function reverseDepreciation(string $depreciationId, string $reason): void;

    /**
     * Revalue an asset.
     *
     * @param string $assetId The asset identifier
     * @param float $newValue The new asset value
     * @param float $newSalvageValue The new salvage value
     * @param \Nexus\FixedAssetDepreciation\Enums\RevaluationType $type The revaluation type
     * @param string $reason The reason for revaluation
     * @param string|null $glAccountId Optional GL account for revaluation reserve
     * @return \Nexus\FixedAssetDepreciation\Entities\AssetRevaluation
     * @throws \Nexus\FixedAssetDepreciation\Exceptions\RevaluationException
     */
    public function revalueAsset(
        string $assetId,
        float $newValue,
        float $newSalvageValue,
        \Nexus\FixedAssetDepreciation\Enums\RevaluationType $type,
        string $reason,
        ?string $glAccountId = null
    ): \Nexus\FixedAssetDepreciation\Entities\AssetRevaluation;

    /**
     * Calculate tax depreciation parallel to book depreciation.
     *
     * @param string $assetId The asset identifier
     * @param string $taxMethod The tax depreciation method (e.g., MACRS)
     * @param int $taxYear The tax year
     * @return \Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount
     * @throws \Nexus\FixedAssetDepreciation\Exceptions\DepreciationCalculationException
     */
    public function calculateTaxDepreciation(string $assetId, string $taxMethod, int $taxYear): \Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

    /**
     * Forecast future depreciation for an asset.
     *
     * @param string $assetId The asset identifier
     * @param int $numberOfPeriods Number of periods to forecast
     * @return \Nexus\FixedAssetDepreciation\ValueObjects\DepreciationForecast
     */
    public function forecastDepreciation(string $assetId, int $numberOfPeriods): \Nexus\FixedAssetDepreciation\ValueObjects\DepreciationForecast;
}
