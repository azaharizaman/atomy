<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Services;

use Nexus\FinancialRatios\Contracts\LiquidityRatioInterface;
use Nexus\FinancialRatios\Enums\RatioCategory;
use Nexus\FinancialRatios\Exceptions\RatioCalculationException;
use Nexus\FinancialRatios\ValueObjects\RatioResult;

/**
 * Calculator for liquidity ratios.
 */
final readonly class LiquidityRatioCalculator implements LiquidityRatioInterface
{
    public function currentRatio(float $currentAssets, float $currentLiabilities): RatioResult
    {
        if ($currentLiabilities === 0.0) {
            throw RatioCalculationException::divisionByZero('Current Ratio', 'current liabilities');
        }

        $value = $currentAssets / $currentLiabilities;

        return new RatioResult(
            ratioName: 'Current Ratio',
            value: $value,
            category: RatioCategory::LIQUIDITY,
            benchmark: 2.0,
            interpretation: $this->interpretCurrentRatio($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function quickRatio(float $currentAssets, float $inventory, float $currentLiabilities): RatioResult
    {
        if ($currentLiabilities === 0.0) {
            throw RatioCalculationException::divisionByZero('Quick Ratio', 'current liabilities');
        }

        $value = ($currentAssets - $inventory) / $currentLiabilities;

        return new RatioResult(
            ratioName: 'Quick Ratio',
            value: $value,
            category: RatioCategory::LIQUIDITY,
            benchmark: 1.0,
            interpretation: $this->interpretQuickRatio($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function cashRatio(float $cash, float $currentLiabilities): RatioResult
    {
        if ($currentLiabilities === 0.0) {
            throw RatioCalculationException::divisionByZero('Cash Ratio', 'current liabilities');
        }

        $value = $cash / $currentLiabilities;

        return new RatioResult(
            ratioName: 'Cash Ratio',
            value: $value,
            category: RatioCategory::LIQUIDITY,
            benchmark: 0.5,
            interpretation: $this->interpretCashRatio($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function workingCapital(float $currentAssets, float $currentLiabilities): float
    {
        return $currentAssets - $currentLiabilities;
    }

    private function interpretCurrentRatio(float $value): string
    {
        if ($value >= 2.0) {
            return 'Strong liquidity position';
        }

        if ($value >= 1.5) {
            return 'Adequate liquidity';
        }

        if ($value >= 1.0) {
            return 'Marginal liquidity - monitor closely';
        }

        return 'Liquidity concern - may have difficulty meeting short-term obligations';
    }

    private function interpretQuickRatio(float $value): string
    {
        if ($value >= 1.5) {
            return 'Excellent quick liquidity';
        }

        if ($value >= 1.0) {
            return 'Adequate quick liquidity';
        }

        return 'May struggle to meet immediate obligations without selling inventory';
    }

    private function interpretCashRatio(float $value): string
    {
        if ($value >= 1.0) {
            return 'Can cover all current liabilities with cash';
        }

        if ($value >= 0.5) {
            return 'Reasonable cash position';
        }

        return 'Limited cash coverage for immediate needs';
    }
}
