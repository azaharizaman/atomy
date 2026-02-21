<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Services;

use DateTimeImmutable;
use Nexus\FixedAssetDepreciation\Contracts\DepreciationCalculatorInterface;
use Nexus\FixedAssetDepreciation\Contracts\Integration\AssetDataProviderInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\DepreciationStatus;
use Nexus\FixedAssetDepreciation\Enums\DepreciationType;
use Nexus\FixedAssetDepreciation\Exceptions\DepreciationCalculationException;
use Nexus\FixedAssetDepreciation\Exceptions\AssetNotDepreciableException;
use Nexus\FixedAssetDepreciation\Entities\AssetDepreciation;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationLife;
use Nexus\FixedAssetDepreciation\ValueObjects\BookValue;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationForecast;
use Nexus\FixedAssetDepreciation\ValueObjects\PeriodForecast;

/**
 * Depreciation Calculator Service
 *
 * Core calculation engine for computing depreciation amounts using
 * various depreciation methods. Supports both book and tax depreciation.
 *
 * @package Nexus\FixedAssetDepreciation\Services
 */
final readonly class DepreciationCalculator implements DepreciationCalculatorInterface
{
    public function __construct(
        private DepreciationMethodFactory $methodFactory,
        private AssetDataProviderInterface $assetProvider,
    ) {}

    public function calculate(
        string $assetId,
        ?\DateTimeInterface $asOfDate = null,
        ?DepreciationType $type = null
    ): DepreciationAmount {
        $asset = $this->assetProvider->getAsset($assetId);
        
        if ($asset === null) {
            throw DepreciationCalculationException::missingRequiredData($assetId, 'asset');
        }

        $cost = $this->assetProvider->getAssetCost($assetId);
        $salvage = $this->assetProvider->getAssetSalvageValue($assetId);
        $usefulLifeMonths = $this->assetProvider->getAssetUsefulLife($assetId);
        $accumulated = $this->assetProvider->getAccumulatedDepreciation($assetId);
        $methodType = $this->assetProvider->getAssetDepreciationMethod($assetId);
        $acquisitionDate = $this->assetProvider->getAssetAcquisitionDate($assetId);

        if ($cost <= 0) {
            throw AssetNotDepreciableException::zeroCost($assetId);
        }

        $depreciableAmount = $cost - $salvage;
        $remainingDepreciation = max(0, $depreciableAmount - $accumulated);

        if ($remainingDepreciation <= 0) {
            return new DepreciationAmount(
                amount: 0.0,
                currency: $asset['currency'] ?? 'USD',
                accumulatedDepreciation: $accumulated
            );
        }

        $method = $this->methodFactory->create($methodType);
        
        $asOfDate = $asOfDate ?? new DateTimeImmutable();
        $periodStart = new DateTimeImmutable($asOfDate->format('Y-m-01'));
        $periodEnd = $periodStart->modify('+1 month -1 day');

        return $method->calculate(
            $cost,
            $salvage,
            $periodStart,
            $periodEnd,
            [
                'useful_life_months' => $usefulLifeMonths,
                'accumulated_depreciation' => $accumulated,
                'remaining_months' => max(0, $usefulLifeMonths - (int)($accumulated / ($depreciableAmount / $usefulLifeMonths))),
                'acquisition_date' => $acquisitionDate,
                'currency' => $asset['currency'] ?? 'USD',
            ]
        );
    }

    public function calculateForPeriod(
        string $assetId,
        string $periodId,
        ?DepreciationType $type = null
    ): AssetDepreciation {
        $asset = $this->assetProvider->getAsset($assetId);
        
        if ($asset === null) {
            throw DepreciationCalculationException::missingRequiredData($assetId, 'asset');
        }

        $cost = $this->assetProvider->getAssetCost($assetId);
        $salvage = $this->assetProvider->getAssetSalvageValue($assetId);
        $accumulated = $this->assetProvider->getAccumulatedDepreciation($assetId);
        $methodType = $this->assetProvider->getAssetDepreciationMethod($assetId);

        $depreciationAmount = $this->calculate($assetId, null, $type);
        
        $bookValueBefore = new BookValue($cost, $salvage, $accumulated);
        $bookValueAfter = $bookValueBefore->depreciate($depreciationAmount);

        return new AssetDepreciation(
            id: sprintf('DEP-%s-%s', $assetId, $periodId),
            assetId: $assetId,
            scheduleId: sprintf('SCH-%s', $assetId),
            periodId: $periodId,
            methodType: $methodType,
            depreciationType: $type ?? DepreciationType::BOOK,
            depreciationAmount: $depreciationAmount,
            bookValueBefore: $bookValueBefore,
            bookValueAfter: $bookValueAfter,
            calculationDate: new DateTimeImmutable(),
            postingDate: null,
            status: DepreciationStatus::CALCULATED
        );
    }

    public function forecast(string $assetId, int $numberOfPeriods): DepreciationForecast
    {
        $asset = $this->assetProvider->getAsset($assetId);
        
        if ($asset === null) {
            throw DepreciationCalculationException::missingRequiredData($assetId, 'asset');
        }

        $cost = $this->assetProvider->getAssetCost($assetId);
        $salvage = $this->assetProvider->getAssetSalvageValue($assetId);
        $usefulLifeMonths = $this->assetProvider->getAssetUsefulLife($assetId);
        $accumulated = $this->assetProvider->getAccumulatedDepreciation($assetId);
        $methodType = $this->assetProvider->getAssetDepreciationMethod($assetId);
        $currency = $asset['currency'] ?? 'USD';

        $method = $this->methodFactory->create($methodType);
        $periodForecasts = [];
        $currentBookValue = $cost - $accumulated;
        $currentAccumulated = $accumulated;
        $totalForecasted = 0.0;
        $startDate = new DateTimeImmutable();

        for ($period = 1; $period <= $numberOfPeriods; $period++) {
            if ($currentBookValue <= $salvage) {
                $periodDate = $startDate->modify(sprintf('+%d months', $period - 1));
                $periodForecasts[] = new PeriodForecast(
                    periodId: $periodDate->format('Y-m'),
                    amount: 0.0,
                    netBookValue: max($salvage, $currentBookValue)
                );
                continue;
            }

            $depreciationAmount = $method->calculate(
                $cost,
                $salvage,
                new DateTimeImmutable(),
                new DateTimeImmutable(),
                [
                    'useful_life_months' => $usefulLifeMonths,
                    'accumulated_depreciation' => $currentAccumulated,
                    'remaining_months' => max(0, $usefulLifeMonths - $period + 1),
                    'currency' => $currency,
                ]
            );

            $periodDate = $startDate->modify(sprintf('+%d months', $period - 1));
            $periodForecasts[] = new PeriodForecast(
                periodId: $periodDate->format('Y-m'),
                amount: $depreciationAmount->amount,
                netBookValue: max($salvage, $currentBookValue - $depreciationAmount->amount)
            );

            $currentAccumulated += $depreciationAmount->amount;
            $currentBookValue = $cost - $currentAccumulated;
            $totalForecasted += $depreciationAmount->amount;
        }

        return DepreciationForecast::fromPeriodForecasts($assetId, $periodForecasts);
    }
}
