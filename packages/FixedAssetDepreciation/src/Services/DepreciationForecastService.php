<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Services;

use DateTimeImmutable;
use Nexus\FixedAssetDepreciation\Contracts\DepreciationMethodInterface;
use Nexus\FixedAssetDepreciation\Contracts\Integration\AssetDataProviderInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationForecast;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationLife;
use Nexus\FixedAssetDepreciation\ValueObjects\PeriodForecast;

/**
 * Service for generating depreciation forecasts.
 *
 * Provides future depreciation projections based on current
 * asset data and selected depreciation method.
 *
 * Tier: 2 (Advanced)
 *
 * @package Nexus\FixedAssetDepreciation\Services
 */
final readonly class DepreciationForecastService
{
    /**
     * Default number of periods to forecast.
     */
    private const DEFAULT_FORECAST_PERIODS = 12;

    public function __construct(
        private DepreciationMethodFactory $methodFactory,
        private AssetDataProviderInterface $assetProvider,
    ) {}

    /**
     * Generate depreciation forecast for an asset.
     *
     * Creates a forecast of depreciation expenses for future periods
     * based on the asset's current state and depreciation method.
     *
     * @param string $assetId The asset identifier
     * @param int $numberOfPeriods Number of periods to forecast (default: 12)
     * @param DepreciationMethodType|null $method Override the depreciation method
     * @param DateTimeImmutable|null $startDate Start date for forecast (default: today)
     * @return DepreciationForecast The depreciation forecast
     * @throws \InvalidArgumentException If parameters are invalid
     */
    public function forecast(
        string $assetId,
        int $numberOfPeriods = self::DEFAULT_FORECAST_PERIODS,
        ?DepreciationMethodType $method = null,
        ?DateTimeImmutable $startDate = null
    ): DepreciationForecast {
        if ($numberOfPeriods <= 0) {
            throw new \InvalidArgumentException(
                sprintf('Number of periods must be positive, got %d', $numberOfPeriods)
            );
        }

        $asset = $this->assetProvider->getAsset($assetId);
        if ($asset === null) {
            throw new \InvalidArgumentException(sprintf('Asset not found: %s', $assetId));
        }

        $cost = $this->assetProvider->getAssetCost($assetId);
        $salvageValue = $this->assetProvider->getAssetSalvageValue($assetId);
        $usefulLifeMonths = $this->assetProvider->getAssetUsefulLife($assetId);
        $methodType = $method ?? $this->assetProvider->getAssetDepreciationMethod($assetId);
        $accumulatedDepreciation = $this->assetProvider->getAccumulatedDepreciation($assetId);

        $startDate = $startDate ?? new DateTimeImmutable();
        
        // Validate parameters
        if ($cost <= 0) {
            throw new \InvalidArgumentException(
                sprintf('Asset cost must be positive for asset %s', $assetId)
            );
        }

        if ($usefulLifeMonths <= 0) {
            throw new \InvalidArgumentException(
                sprintf('Useful life must be positive for asset %s', $assetId)
            );
        }

        if ($salvageValue < 0) {
            throw new \InvalidArgumentException(
                sprintf('Salvage value cannot be negative for asset %s', $assetId)
            );
        }

        if ($salvageValue > $cost) {
            throw new \InvalidArgumentException(
                sprintf('Salvage value cannot exceed cost for asset %s', $assetId)
            );
        }

        // Create depreciation method
        $depreciationMethod = $this->methodFactory->create($methodType);
        
        // Calculate remaining depreciable amount
        $currentBookValue = $cost - $accumulatedDepreciation;
        $remainingDepreciable = max(0, $currentBookValue - $salvageValue);

        // Generate period forecasts
        $periodForecasts = $this->generateForecasts(
            $depreciationMethod,
            $cost,
            $salvageValue,
            $accumulatedDepreciation,
            $usefulLifeMonths,
            $startDate,
            $numberOfPeriods,
            $assetId
        );

        return DepreciationForecast::fromPeriodForecasts($assetId, $periodForecasts);
    }

    /**
     * Generate forecast for remaining useful life.
     *
     * Creates a forecast that extends to the end of the asset's
     * remaining useful life.
     *
     * @param string $assetId The asset identifier
     * @param DepreciationMethodType|null $method Override the depreciation method
     * @param DateTimeImmutable|null $startDate Start date for forecast
     * @return DepreciationForecast The depreciation forecast
     */
    public function forecastRemainingLife(
        string $assetId,
        ?DepreciationMethodType $method = null,
        ?DateTimeImmutable $startDate = null
    ): DepreciationForecast {
        $usefulLifeMonths = $this->assetProvider->getAssetUsefulLife($assetId);
        $accumulatedDepreciation = $this->assetProvider->getAccumulatedDepreciation($assetId);
        
        // Estimate remaining months based on accumulated depreciation
        $cost = $this->assetProvider->getAssetCost($assetId);
        $salvageValue = $this->assetProvider->getAssetSalvageValue($assetId);
        $depreciableAmount = $cost - $salvageValue;
        
        if ($depreciableAmount <= 0) {
            return new DepreciationForecast(
                periods: [],
                totalDepreciation: 0.0,
                averageDepreciation: 0.0,
                numberOfPeriods: 0
            );
        }
        
        $depreciationPercentage = $accumulatedDepreciation / $depreciableAmount;
        $remainingMonths = (int) ceil($usefulLifeMonths * (1 - $depreciationPercentage));
        $remainingMonths = max(0, min($remainingMonths, $usefulLifeMonths));
        
        // Return empty forecast if no remaining months
        if ($remainingMonths <= 0) {
            return new DepreciationForecast(
                periods: [],
                totalDepreciation: 0.0,
                averageDepreciation: 0.0,
                numberOfPeriods: 0
            );
        }
        
        return $this->forecast(
            $assetId,
            $remainingMonths,
            $method,
            $startDate
        );
    }

    /**
     * Calculate annual depreciation forecast.
     *
     * Creates a yearly summary of depreciation forecasts.
     *
     * @param string $assetId The asset identifier
     * @param int $numberOfYears Number of years to forecast
     * @param DepreciationMethodType|null $method Override the depreciation method
     * @return array<int, float> Array keyed by year with total depreciation
     */
    public function forecastAnnual(
        string $assetId,
        int $numberOfYears = 5,
        ?DepreciationMethodType $method = null
    ): array {
        // Return empty array for zero years
        if ($numberOfYears <= 0) {
            return [];
        }
        
        $monthsToForecast = $numberOfYears * 12;
        
        $forecast = $this->forecast(
            $assetId,
            $monthsToForecast,
            $method
        );
        
        return $forecast->getYearlySummary();
    }

    /**
     * Get total remaining depreciation.
     *
     * Calculates the total depreciation remaining to be expensed
     * over the asset's useful life.
     *
     * @param string $assetId The asset identifier
     * @return float Total remaining depreciation
     */
    public function getTotalRemainingDepreciation(string $assetId): float
    {
        $cost = $this->assetProvider->getAssetCost($assetId);
        $salvageValue = $this->assetProvider->getAssetSalvageValue($assetId);
        $accumulatedDepreciation = $this->assetProvider->getAccumulatedDepreciation($assetId);
        
        $depreciableAmount = $cost - $salvageValue;
        $remainingDepreciation = $depreciableAmount - $accumulatedDepreciation;
        
        return max(0, $remainingDepreciation);
    }

    /**
     * Calculate monthly depreciation projection.
     *
     * @param string $assetId The asset identifier
     * @param DepreciationMethodType|null $method Override the depreciation method
     * @return float Projected monthly depreciation
     */
    public function getProjectedMonthlyDepreciation(
        string $assetId,
        ?DepreciationMethodType $method = null
    ): float {
        $remainingDepreciation = $this->getTotalRemainingDepreciation($assetId);
        $usefulLifeMonths = $this->assetProvider->getAssetUsefulLife($assetId);
        $accumulatedDepreciation = $this->assetProvider->getAccumulatedDepreciation($assetId);
        
        $cost = $this->assetProvider->getAssetCost($assetId);
        $salvageValue = $this->assetProvider->getAssetSalvageValue($assetId);
        $depreciableAmount = $cost - $salvageValue;
        
        if ($depreciableAmount <= 0) {
            return 0.0;
        }
        
        $depreciationPercentage = $accumulatedDepreciation / $depreciableAmount;
        $remainingMonths = (int) ceil($usefulLifeMonths * (1 - $depreciationPercentage));
        $remainingMonths = max(1, $remainingMonths);
        
        return $remainingDepreciation / $remainingMonths;
    }

    /**
     * Generate forecast data.
     *
     * @param DepreciationMethodInterface $method The depreciation method
     * @param float $cost Original asset cost
     * @param float $salvageValue Salvage value
     * @param float $accumulatedDepreciation Current accumulated depreciation
     * @param int $usefulLifeMonths Total useful life in months
     * @param DateTimeImmutable $startDate Forecast start date
     * @param int $numberOfPeriods Number of periods to forecast
     * @param string $assetId Asset identifier for ID generation
     * @return array<PeriodForecast> Array of period forecasts
     */
    private function generateForecasts(
        DepreciationMethodInterface $method,
        float $cost,
        float $salvageValue,
        float $accumulatedDepreciation,
        int $usefulLifeMonths,
        DateTimeImmutable $startDate,
        int $numberOfPeriods,
        string $assetId
    ): array {
        $forecasts = [];
        $currentBookValue = $cost - $accumulatedDepreciation;
        $currentAccumulated = $accumulatedDepreciation;
        
        for ($month = 1; $month <= $numberOfPeriods; $month++) {
            // Stop if asset is fully depreciated
            if ($currentBookValue <= $salvageValue) {
                break;
            }
            
            $periodStartDate = $startDate->modify(sprintf('+%d months', $month - 1));
            $periodEndDate = $startDate->modify(sprintf('+%d months - 1 day', $month));
            $periodId = $periodStartDate->format('Y-m');
            
            // Calculate remaining months from original useful life
            $remainingUsefulLife = max(0, $usefulLifeMonths - ($month - 1));
            
            $depreciationAmount = $method->calculate(
                $cost,
                $salvageValue,
                $periodStartDate,
                $periodEndDate,
                [
                    'useful_life_months' => $remainingUsefulLife,
                    'accumulated_depreciation' => $currentAccumulated,
                    'remaining_months' => max(0, $remainingUsefulLife - $month + 1),
                    'acquisition_date' => $startDate,
                    'current_year' => (int) ceil($month / 12),
                ]
            );
            
            // Ensure we don't depreciate below salvage value
            $depreciationAmount = min(
                $depreciationAmount->amount,
                $currentBookValue - $salvageValue
            );
            
            $currentAccumulated += $depreciationAmount;
            $currentBookValue -= $depreciationAmount;
            
            $forecasts[] = PeriodForecast::create(
                periodId: $periodId,
                amount: $depreciationAmount,
                netBookValue: $currentBookValue,
                accumulatedDepreciation: $currentAccumulated
            );
        }
        
        return $forecasts;
    }
}
