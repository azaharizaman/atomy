<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Services;

use Nexus\FinancialRatios\Contracts\EfficiencyRatioInterface;
use Nexus\FinancialRatios\Enums\RatioCategory;
use Nexus\FinancialRatios\Exceptions\RatioCalculationException;
use Nexus\FinancialRatios\ValueObjects\RatioResult;

/**
 * Calculator for efficiency ratios.
 */
final readonly class EfficiencyRatioCalculator implements EfficiencyRatioInterface
{
    public function assetTurnover(float $revenue, float $averageTotalAssets): RatioResult
    {
        if ($averageTotalAssets === 0.0) {
            throw RatioCalculationException::divisionByZero('Asset Turnover', 'average total assets');
        }

        $value = $revenue / $averageTotalAssets;

        return new RatioResult(
            ratioName: 'Asset Turnover',
            value: $value,
            category: RatioCategory::EFFICIENCY,
            benchmark: 1.0,
            interpretation: $this->interpretAssetTurnover($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function inventoryTurnover(float $cogs, float $averageInventory): RatioResult
    {
        if ($averageInventory === 0.0) {
            throw RatioCalculationException::divisionByZero('Inventory Turnover', 'average inventory');
        }

        $value = $cogs / $averageInventory;

        return new RatioResult(
            ratioName: 'Inventory Turnover',
            value: $value,
            category: RatioCategory::EFFICIENCY,
            benchmark: 6.0,
            interpretation: $this->interpretInventoryTurnover($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function receivablesTurnover(float $revenue, float $averageReceivables): RatioResult
    {
        if ($averageReceivables === 0.0) {
            throw RatioCalculationException::divisionByZero('Receivables Turnover', 'average receivables');
        }

        $value = $revenue / $averageReceivables;

        return new RatioResult(
            ratioName: 'Receivables Turnover',
            value: $value,
            category: RatioCategory::EFFICIENCY,
            benchmark: 8.0,
            interpretation: $this->interpretReceivablesTurnover($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function payablesTurnover(float $cogs, float $averagePayables): RatioResult
    {
        if ($averagePayables === 0.0) {
            throw RatioCalculationException::divisionByZero('Payables Turnover', 'average payables');
        }

        $value = $cogs / $averagePayables;

        return new RatioResult(
            ratioName: 'Payables Turnover',
            value: $value,
            category: RatioCategory::EFFICIENCY,
            benchmark: 6.0,
            interpretation: 'Measures how quickly payables are settled',
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function daysSalesOutstanding(float $receivablesTurnover): float
    {
        if ($receivablesTurnover === 0.0) {
            return 0.0;
        }

        return 365.0 / $receivablesTurnover;
    }

    public function daysInventoryOutstanding(float $inventoryTurnover): float
    {
        if ($inventoryTurnover === 0.0) {
            return 0.0;
        }

        return 365.0 / $inventoryTurnover;
    }

    public function daysPayablesOutstanding(float $payablesTurnover): float
    {
        if ($payablesTurnover === 0.0) {
            return 0.0;
        }

        return 365.0 / $payablesTurnover;
    }

    public function cashConversionCycle(float $dio, float $dso, float $dpo): float
    {
        return $dio + $dso - $dpo;
    }

    private function interpretAssetTurnover(float $value): string
    {
        if ($value >= 2.0) {
            return 'Excellent asset utilization';
        }

        if ($value >= 1.0) {
            return 'Good asset turnover';
        }

        return 'Assets may be underutilized';
    }

    private function interpretInventoryTurnover(float $value): string
    {
        if ($value >= 10.0) {
            return 'Excellent inventory management';
        }

        if ($value >= 6.0) {
            return 'Good inventory turnover';
        }

        if ($value >= 3.0) {
            return 'Moderate inventory turnover';
        }

        return 'Slow inventory movement - potential obsolescence risk';
    }

    private function interpretReceivablesTurnover(float $value): string
    {
        if ($value >= 12.0) {
            return 'Excellent collection efficiency';
        }

        if ($value >= 6.0) {
            return 'Good receivables management';
        }

        return 'Slow collections - review credit policies';
    }
}
