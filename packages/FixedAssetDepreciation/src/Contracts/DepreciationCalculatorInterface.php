<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Contracts;

use Nexus\FixedAssetDepreciation\Enums\DepreciationType;

/**
 * Interface for depreciation calculation operations.
 */
interface DepreciationCalculatorInterface
{
    /**
     * Calculate depreciation amount for an asset.
     *
     * @param string $assetId The asset identifier
     * @param \DateTimeInterface|null $asOfDate Calculate as of specific date
     * @param DepreciationType|null $type Book or Tax depreciation
     * @return \Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount
     */
    public function calculate(
        string $assetId,
        ?\DateTimeInterface $asOfDate = null,
        ?DepreciationType $type = null
    ): \Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

    /**
     * Calculate depreciation for a specific period.
     *
     * @param string $assetId The asset identifier
     * @param string $periodId The period identifier
     * @param DepreciationType|null $type Book or Tax depreciation
     * @return \Nexus\FixedAssetDepreciation\Entities\AssetDepreciation
     */
    public function calculateForPeriod(
        string $assetId,
        string $periodId,
        ?DepreciationType $type = null
    ): \Nexus\FixedAssetDepreciation\Entities\AssetDepreciation;

    /**
     * Forecast future depreciation.
     *
     * @param string $assetId The asset identifier
     * @param int $numberOfPeriods Number of periods to forecast
     * @return \Nexus\FixedAssetDepreciation\ValueObjects\DepreciationForecast
     */
    public function forecast(string $assetId, int $numberOfPeriods): \Nexus\FixedAssetDepreciation\ValueObjects\DepreciationForecast;
}
