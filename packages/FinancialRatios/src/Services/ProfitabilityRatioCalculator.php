<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Services;

use Nexus\FinancialRatios\Contracts\ProfitabilityRatioInterface;
use Nexus\FinancialRatios\Enums\RatioCategory;
use Nexus\FinancialRatios\Exceptions\RatioCalculationException;
use Nexus\FinancialRatios\ValueObjects\RatioResult;

/**
 * Calculator for profitability ratios.
 */
final readonly class ProfitabilityRatioCalculator implements ProfitabilityRatioInterface
{
    public function grossProfitMargin(float $grossProfit, float $revenue): RatioResult
    {
        if ($revenue === 0.0) {
            throw RatioCalculationException::divisionByZero('Gross Profit Margin', 'revenue');
        }

        $value = $grossProfit / $revenue;

        return new RatioResult(
            ratioName: 'Gross Profit Margin',
            value: $value,
            category: RatioCategory::PROFITABILITY,
            benchmark: 0.30,
            interpretation: $this->interpretGrossMargin($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function operatingProfitMargin(float $operatingIncome, float $revenue): RatioResult
    {
        if ($revenue === 0.0) {
            throw RatioCalculationException::divisionByZero('Operating Profit Margin', 'revenue');
        }

        $value = $operatingIncome / $revenue;

        return new RatioResult(
            ratioName: 'Operating Profit Margin',
            value: $value,
            category: RatioCategory::PROFITABILITY,
            benchmark: 0.15,
            interpretation: $this->interpretOperatingMargin($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function netProfitMargin(float $netIncome, float $revenue): RatioResult
    {
        if ($revenue === 0.0) {
            throw RatioCalculationException::divisionByZero('Net Profit Margin', 'revenue');
        }

        $value = $netIncome / $revenue;

        return new RatioResult(
            ratioName: 'Net Profit Margin',
            value: $value,
            category: RatioCategory::PROFITABILITY,
            benchmark: 0.10,
            interpretation: $this->interpretNetMargin($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function returnOnAssets(float $netIncome, float $totalAssets): RatioResult
    {
        if ($totalAssets === 0.0) {
            throw RatioCalculationException::divisionByZero('Return on Assets', 'total assets');
        }

        $value = $netIncome / $totalAssets;

        return new RatioResult(
            ratioName: 'Return on Assets (ROA)',
            value: $value,
            category: RatioCategory::PROFITABILITY,
            benchmark: 0.05,
            interpretation: $this->interpretRoa($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function returnOnEquity(float $netIncome, float $totalEquity): RatioResult
    {
        if ($totalEquity === 0.0) {
            throw RatioCalculationException::divisionByZero('Return on Equity', 'total equity');
        }

        $value = $netIncome / $totalEquity;

        return new RatioResult(
            ratioName: 'Return on Equity (ROE)',
            value: $value,
            category: RatioCategory::PROFITABILITY,
            benchmark: 0.15,
            interpretation: $this->interpretRoe($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function returnOnInvestedCapital(float $nopat, float $investedCapital): RatioResult
    {
        if ($investedCapital === 0.0) {
            throw RatioCalculationException::divisionByZero('Return on Invested Capital', 'invested capital');
        }

        $value = $nopat / $investedCapital;

        return new RatioResult(
            ratioName: 'Return on Invested Capital (ROIC)',
            value: $value,
            category: RatioCategory::PROFITABILITY,
            benchmark: 0.10,
            interpretation: 'Measures return generated on all invested capital',
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    private function interpretGrossMargin(float $value): string
    {
        if ($value >= 0.40) {
            return 'Strong gross margin indicating pricing power or cost efficiency';
        }

        if ($value >= 0.25) {
            return 'Healthy gross margin';
        }

        return 'Low gross margin - review pricing strategy or cost structure';
    }

    private function interpretOperatingMargin(float $value): string
    {
        if ($value >= 0.20) {
            return 'Excellent operational efficiency';
        }

        if ($value >= 0.10) {
            return 'Good operating margin';
        }

        return 'Operating efficiency needs improvement';
    }

    private function interpretNetMargin(float $value): string
    {
        if ($value >= 0.15) {
            return 'Strong net profitability';
        }

        if ($value >= 0.05) {
            return 'Adequate net margin';
        }

        if ($value > 0) {
            return 'Low but positive profitability';
        }

        return 'Operating at a loss';
    }

    private function interpretRoa(float $value): string
    {
        if ($value >= 0.10) {
            return 'Excellent asset utilization';
        }

        if ($value >= 0.05) {
            return 'Good return on assets';
        }

        return 'Assets not generating sufficient returns';
    }

    private function interpretRoe(float $value): string
    {
        if ($value >= 0.20) {
            return 'Excellent return for shareholders';
        }

        if ($value >= 0.10) {
            return 'Adequate return on equity';
        }

        return 'Low return on shareholder equity';
    }
}
