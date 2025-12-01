<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Services;

use Nexus\FinancialRatios\Contracts\LeverageRatioInterface;
use Nexus\FinancialRatios\Enums\RatioCategory;
use Nexus\FinancialRatios\Exceptions\RatioCalculationException;
use Nexus\FinancialRatios\ValueObjects\RatioResult;

/**
 * Calculator for leverage ratios.
 */
final readonly class LeverageRatioCalculator implements LeverageRatioInterface
{
    public function debtToEquity(float $totalDebt, float $totalEquity): RatioResult
    {
        if ($totalEquity === 0.0) {
            throw RatioCalculationException::divisionByZero('Debt to Equity', 'total equity');
        }

        $value = $totalDebt / $totalEquity;

        return new RatioResult(
            ratioName: 'Debt to Equity Ratio',
            value: $value,
            category: RatioCategory::LEVERAGE,
            benchmark: 1.0,
            interpretation: $this->interpretDebtToEquity($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function debtToAssets(float $totalDebt, float $totalAssets): RatioResult
    {
        if ($totalAssets === 0.0) {
            throw RatioCalculationException::divisionByZero('Debt to Assets', 'total assets');
        }

        $value = $totalDebt / $totalAssets;

        return new RatioResult(
            ratioName: 'Debt to Assets Ratio',
            value: $value,
            category: RatioCategory::LEVERAGE,
            benchmark: 0.5,
            interpretation: $this->interpretDebtToAssets($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function interestCoverage(float $ebit, float $interestExpense): RatioResult
    {
        if ($interestExpense === 0.0) {
            return new RatioResult(
                ratioName: 'Interest Coverage Ratio',
                value: PHP_FLOAT_MAX,
                category: RatioCategory::LEVERAGE,
                benchmark: 3.0,
                interpretation: 'No interest expense - infinite coverage',
                calculatedAt: new \DateTimeImmutable(),
            );
        }

        $value = $ebit / $interestExpense;

        return new RatioResult(
            ratioName: 'Interest Coverage Ratio',
            value: $value,
            category: RatioCategory::LEVERAGE,
            benchmark: 3.0,
            interpretation: $this->interpretInterestCoverage($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function debtServiceCoverage(float $netOperatingIncome, float $totalDebtService): RatioResult
    {
        if ($totalDebtService === 0.0) {
            throw RatioCalculationException::divisionByZero('Debt Service Coverage', 'total debt service');
        }

        $value = $netOperatingIncome / $totalDebtService;

        return new RatioResult(
            ratioName: 'Debt Service Coverage Ratio',
            value: $value,
            category: RatioCategory::LEVERAGE,
            benchmark: 1.25,
            interpretation: $this->interpretDebtServiceCoverage($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function equityMultiplier(float $totalAssets, float $totalEquity): RatioResult
    {
        if ($totalEquity === 0.0) {
            throw RatioCalculationException::divisionByZero('Equity Multiplier', 'total equity');
        }

        $value = $totalAssets / $totalEquity;

        return new RatioResult(
            ratioName: 'Equity Multiplier',
            value: $value,
            category: RatioCategory::LEVERAGE,
            benchmark: 2.0,
            interpretation: 'Measures financial leverage - higher values indicate more debt financing',
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function longTermDebtToCapitalization(float $longTermDebt, float $totalCapitalization): RatioResult
    {
        if ($totalCapitalization === 0.0) {
            throw RatioCalculationException::divisionByZero('Long-term Debt to Capitalization', 'total capitalization');
        }

        $value = $longTermDebt / $totalCapitalization;

        return new RatioResult(
            ratioName: 'Long-term Debt to Capitalization',
            value: $value,
            category: RatioCategory::LEVERAGE,
            benchmark: 0.35,
            interpretation: 'Measures proportion of long-term debt in capital structure',
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    private function interpretDebtToEquity(float $value): string
    {
        if ($value <= 0.5) {
            return 'Conservative leverage - low financial risk';
        }

        if ($value <= 1.0) {
            return 'Moderate leverage - balanced capital structure';
        }

        if ($value <= 2.0) {
            return 'Aggressive leverage - higher financial risk';
        }

        return 'High leverage - significant financial risk';
    }

    private function interpretDebtToAssets(float $value): string
    {
        if ($value <= 0.3) {
            return 'Low debt level - strong solvency';
        }

        if ($value <= 0.5) {
            return 'Moderate debt level';
        }

        return 'High debt level - solvency risk';
    }

    private function interpretInterestCoverage(float $value): string
    {
        if ($value >= 5.0) {
            return 'Excellent ability to service debt';
        }

        if ($value >= 3.0) {
            return 'Comfortable interest coverage';
        }

        if ($value >= 1.5) {
            return 'Marginal coverage - monitor closely';
        }

        return 'Struggling to cover interest payments';
    }

    private function interpretDebtServiceCoverage(float $value): string
    {
        if ($value >= 1.5) {
            return 'Strong ability to service all debt obligations';
        }

        if ($value >= 1.25) {
            return 'Adequate debt service coverage';
        }

        if ($value >= 1.0) {
            return 'Barely covering debt payments';
        }

        return 'Cannot cover debt service from operations';
    }
}
