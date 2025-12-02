<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Services;

use Nexus\FinancialRatios\Contracts\CashFlowRatioInterface;
use Nexus\FinancialRatios\Enums\RatioCategory;
use Nexus\FinancialRatios\Exceptions\RatioCalculationException;
use Nexus\FinancialRatios\ValueObjects\RatioResult;

/**
 * Calculator for cash flow ratios.
 */
final readonly class CashFlowRatioCalculator implements CashFlowRatioInterface
{
    public function operatingCashFlowRatio(float $operatingCashFlow, float $currentLiabilities): RatioResult
    {
        if ($currentLiabilities === 0.0) {
            throw RatioCalculationException::divisionByZero('Operating Cash Flow Ratio', 'current liabilities');
        }

        $value = $operatingCashFlow / $currentLiabilities;

        return new RatioResult(
            ratioName: 'Operating Cash Flow Ratio',
            value: $value,
            category: RatioCategory::CASH_FLOW,
            benchmark: 1.0,
            interpretation: $this->interpretOperatingCFRatio($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function freeCashFlow(float $operatingCashFlow, float $capitalExpenditures): float
    {
        return $operatingCashFlow - $capitalExpenditures;
    }

    public function cashFlowMargin(float $operatingCashFlow, float $revenue): RatioResult
    {
        if ($revenue === 0.0) {
            throw RatioCalculationException::divisionByZero('Cash Flow Margin', 'revenue');
        }

        $value = $operatingCashFlow / $revenue;

        return new RatioResult(
            ratioName: 'Cash Flow Margin',
            value: $value,
            category: RatioCategory::CASH_FLOW,
            benchmark: 0.10,
            interpretation: $this->interpretCashFlowMargin($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function cashFlowToDebt(float $operatingCashFlow, float $totalDebt): RatioResult
    {
        if ($totalDebt === 0.0) {
            return new RatioResult(
                ratioName: 'Cash Flow to Debt Ratio',
                value: PHP_FLOAT_MAX,
                category: RatioCategory::CASH_FLOW,
                benchmark: 0.20,
                interpretation: 'No debt - infinite coverage',
                calculatedAt: new \DateTimeImmutable(),
            );
        }

        $value = $operatingCashFlow / $totalDebt;

        return new RatioResult(
            ratioName: 'Cash Flow to Debt Ratio',
            value: $value,
            category: RatioCategory::CASH_FLOW,
            benchmark: 0.20,
            interpretation: $this->interpretCashFlowToDebt($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function cashFlowCoverage(float $operatingCashFlow, float $debtService): RatioResult
    {
        if ($debtService === 0.0) {
            throw RatioCalculationException::divisionByZero('Cash Flow Coverage', 'debt service');
        }

        $value = $operatingCashFlow / $debtService;

        return new RatioResult(
            ratioName: 'Cash Flow Coverage Ratio',
            value: $value,
            category: RatioCategory::CASH_FLOW,
            benchmark: 1.2,
            interpretation: $this->interpretCashFlowCoverage($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    public function capitalExpenditureRatio(float $operatingCashFlow, float $capitalExpenditures): RatioResult
    {
        if ($capitalExpenditures === 0.0) {
            return new RatioResult(
                ratioName: 'Capital Expenditure Ratio',
                value: PHP_FLOAT_MAX,
                category: RatioCategory::CASH_FLOW,
                benchmark: 1.0,
                interpretation: 'No capital expenditures',
                calculatedAt: new \DateTimeImmutable(),
            );
        }

        $value = $operatingCashFlow / $capitalExpenditures;

        return new RatioResult(
            ratioName: 'Capital Expenditure Ratio',
            value: $value,
            category: RatioCategory::CASH_FLOW,
            benchmark: 1.0,
            interpretation: $this->interpretCapexRatio($value),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    private function interpretOperatingCFRatio(float $value): string
    {
        if ($value >= 1.5) {
            return 'Strong operating cash flow coverage';
        }

        if ($value >= 1.0) {
            return 'Adequate cash flow to cover current liabilities';
        }

        return 'Operating cash flow may be insufficient for current obligations';
    }

    private function interpretCashFlowMargin(float $value): string
    {
        if ($value >= 0.15) {
            return 'Excellent cash conversion from sales';
        }

        if ($value >= 0.08) {
            return 'Good cash flow margin';
        }

        return 'Low cash conversion - review working capital management';
    }

    private function interpretCashFlowToDebt(float $value): string
    {
        if ($value >= 0.30) {
            return 'Strong cash flow relative to debt';
        }

        if ($value >= 0.15) {
            return 'Adequate cash flow for debt servicing';
        }

        return 'May struggle to repay debt from operations';
    }

    private function interpretCashFlowCoverage(float $value): string
    {
        if ($value >= 2.0) {
            return 'Excellent debt service coverage from cash flow';
        }

        if ($value >= 1.2) {
            return 'Comfortable coverage';
        }

        if ($value >= 1.0) {
            return 'Barely covering debt service';
        }

        return 'Cash flow insufficient for debt obligations';
    }

    private function interpretCapexRatio(float $value): string
    {
        if ($value >= 2.0) {
            return 'Excellent ability to fund capital expenditures';
        }

        if ($value >= 1.0) {
            return 'Operations can fund capital needs';
        }

        return 'May need external financing for capital expenditures';
    }
}
