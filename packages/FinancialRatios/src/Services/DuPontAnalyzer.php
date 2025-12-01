<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Services;

use Nexus\FinancialRatios\ValueObjects\RatioInput;
use Nexus\FinancialRatios\ValueObjects\RatioResult;
use Nexus\FinancialRatios\Enums\RatioType;
use Nexus\FinancialRatios\Enums\RatioCategory;
use Nexus\FinancialRatios\Exceptions\RatioCalculationException;

/**
 * DuPont Analysis - Decomposition of Return on Equity
 *
 * 3-Factor DuPont: ROE = Net Profit Margin × Asset Turnover × Equity Multiplier
 * 5-Factor DuPont: Further decomposes into tax burden, interest burden, operating margin
 */
final readonly class DuPontAnalyzer
{
    /**
     * Perform 3-factor DuPont analysis
     *
     * @param RatioInput $input Financial data for analysis
     * @return array{
     *     roe: RatioResult,
     *     net_profit_margin: RatioResult,
     *     asset_turnover: RatioResult,
     *     equity_multiplier: RatioResult,
     *     decomposition: array<string, float>
     * }
     * @throws RatioCalculationException
     */
    public function analyzeThreeFactor(RatioInput $input): array
    {
        $netIncome = $input->netIncome;
        $revenue = $input->revenue;
        $totalAssets = $input->totalAssets;
        $shareholdersEquity = $input->shareholdersEquity;

        if ($revenue === null || $revenue == 0) {
            throw new RatioCalculationException('Revenue is required and cannot be zero for DuPont analysis');
        }

        if ($totalAssets === null || $totalAssets == 0) {
            throw new RatioCalculationException('Total assets is required and cannot be zero for DuPont analysis');
        }

        if ($shareholdersEquity === null || $shareholdersEquity == 0) {
            throw new RatioCalculationException('Shareholders equity is required and cannot be zero for DuPont analysis');
        }

        // Calculate components
        $netProfitMargin = $netIncome / $revenue;
        $assetTurnover = $revenue / $totalAssets;
        $equityMultiplier = $totalAssets / $shareholdersEquity;

        // Calculate ROE using DuPont formula
        $roe = $netProfitMargin * $assetTurnover * $equityMultiplier;

        return [
            'roe' => new RatioResult(
                type: RatioType::DUPONT_ROE,
                value: $roe,
                category: RatioCategory::PROFITABILITY,
                formula: 'Net Profit Margin × Asset Turnover × Equity Multiplier',
                interpretation: $this->interpretRoe($roe)
            ),
            'net_profit_margin' => new RatioResult(
                type: RatioType::NET_PROFIT_MARGIN,
                value: $netProfitMargin,
                category: RatioCategory::PROFITABILITY,
                formula: 'Net Income / Revenue',
                interpretation: $this->interpretNetProfitMargin($netProfitMargin)
            ),
            'asset_turnover' => new RatioResult(
                type: RatioType::ASSET_TURNOVER,
                value: $assetTurnover,
                category: RatioCategory::EFFICIENCY,
                formula: 'Revenue / Total Assets',
                interpretation: $this->interpretAssetTurnover($assetTurnover)
            ),
            'equity_multiplier' => new RatioResult(
                type: RatioType::EQUITY_MULTIPLIER,
                value: $equityMultiplier,
                category: RatioCategory::LEVERAGE,
                formula: 'Total Assets / Shareholders Equity',
                interpretation: $this->interpretEquityMultiplier($equityMultiplier)
            ),
            'decomposition' => [
                'net_profit_margin_contribution' => $netProfitMargin,
                'asset_turnover_contribution' => $assetTurnover,
                'equity_multiplier_contribution' => $equityMultiplier,
                'calculated_roe' => $roe,
            ],
        ];
    }

    /**
     * Perform 5-factor DuPont analysis
     *
     * ROE = Tax Burden × Interest Burden × Operating Margin × Asset Turnover × Equity Multiplier
     *
     * @param RatioInput $input Financial data for analysis
     * @return array{
     *     roe: RatioResult,
     *     tax_burden: float,
     *     interest_burden: float,
     *     operating_margin: float,
     *     asset_turnover: float,
     *     equity_multiplier: float,
     *     decomposition: array<string, float>
     * }
     * @throws RatioCalculationException
     */
    public function analyzeFiveFactor(RatioInput $input): array
    {
        $netIncome = $input->netIncome;
        $ebt = $input->earningsBeforeTax ?? $netIncome; // EBT
        $ebit = $input->operatingIncome ?? $ebt; // EBIT
        $revenue = $input->revenue;
        $totalAssets = $input->totalAssets;
        $shareholdersEquity = $input->shareholdersEquity;

        if ($revenue === null || $revenue == 0) {
            throw new RatioCalculationException('Revenue is required and cannot be zero');
        }

        if ($totalAssets === null || $totalAssets == 0) {
            throw new RatioCalculationException('Total assets is required and cannot be zero');
        }

        if ($shareholdersEquity === null || $shareholdersEquity == 0) {
            throw new RatioCalculationException('Shareholders equity is required and cannot be zero');
        }

        // Tax Burden = Net Income / EBT (how much profit retained after taxes)
        $taxBurden = $ebt != 0 ? $netIncome / $ebt : 0;

        // Interest Burden = EBT / EBIT (how much profit retained after interest)
        $interestBurden = $ebit != 0 ? $ebt / $ebit : 0;

        // Operating Margin = EBIT / Revenue
        $operatingMargin = $ebit / $revenue;

        // Asset Turnover = Revenue / Total Assets
        $assetTurnover = $revenue / $totalAssets;

        // Equity Multiplier = Total Assets / Shareholders Equity
        $equityMultiplier = $totalAssets / $shareholdersEquity;

        // Calculate ROE using 5-factor DuPont formula
        $roe = $taxBurden * $interestBurden * $operatingMargin * $assetTurnover * $equityMultiplier;

        return [
            'roe' => new RatioResult(
                type: RatioType::DUPONT_ROE,
                value: $roe,
                category: RatioCategory::PROFITABILITY,
                formula: 'Tax Burden × Interest Burden × Operating Margin × Asset Turnover × Equity Multiplier',
                interpretation: $this->interpretRoe($roe)
            ),
            'tax_burden' => $taxBurden,
            'interest_burden' => $interestBurden,
            'operating_margin' => $operatingMargin,
            'asset_turnover' => $assetTurnover,
            'equity_multiplier' => $equityMultiplier,
            'decomposition' => [
                'tax_burden' => $taxBurden,
                'interest_burden' => $interestBurden,
                'operating_margin' => $operatingMargin,
                'asset_turnover' => $assetTurnover,
                'equity_multiplier' => $equityMultiplier,
                'calculated_roe' => $roe,
            ],
        ];
    }

    /**
     * Compare DuPont components between two periods
     *
     * @param RatioInput $current Current period data
     * @param RatioInput $previous Previous period data
     * @return array<string, array{current: float, previous: float, change: float, change_percent: float}>
     */
    public function compareComponents(RatioInput $current, RatioInput $previous): array
    {
        $currentAnalysis = $this->analyzeThreeFactor($current);
        $previousAnalysis = $this->analyzeThreeFactor($previous);

        $components = ['net_profit_margin', 'asset_turnover', 'equity_multiplier'];
        $comparison = [];

        foreach ($components as $component) {
            $currentValue = $currentAnalysis['decomposition']["{$component}_contribution"];
            $previousValue = $previousAnalysis['decomposition']["{$component}_contribution"];
            $change = $currentValue - $previousValue;
            $changePercent = $previousValue != 0 ? ($change / abs($previousValue)) * 100 : 0;

            $comparison[$component] = [
                'current' => $currentValue,
                'previous' => $previousValue,
                'change' => $change,
                'change_percent' => $changePercent,
            ];
        }

        // Add ROE comparison
        $currentRoe = $currentAnalysis['roe']->value;
        $previousRoe = $previousAnalysis['roe']->value;
        $roeChange = $currentRoe - $previousRoe;
        $roeChangePercent = $previousRoe != 0 ? ($roeChange / abs($previousRoe)) * 100 : 0;

        $comparison['roe'] = [
            'current' => $currentRoe,
            'previous' => $previousRoe,
            'change' => $roeChange,
            'change_percent' => $roeChangePercent,
        ];

        return $comparison;
    }

    private function interpretRoe(float $roe): string
    {
        $percentage = $roe * 100;

        return match (true) {
            $percentage >= 20 => 'Excellent - Strong return on equity indicating efficient use of shareholder capital',
            $percentage >= 15 => 'Good - Above average return on equity',
            $percentage >= 10 => 'Average - Acceptable return on equity',
            $percentage >= 5 => 'Below Average - Consider strategies to improve profitability or efficiency',
            default => 'Poor - Significant improvement needed in profitability, efficiency, or leverage',
        };
    }

    private function interpretNetProfitMargin(float $margin): string
    {
        $percentage = $margin * 100;

        return match (true) {
            $percentage >= 20 => 'Excellent profitability - Strong pricing power and cost control',
            $percentage >= 10 => 'Good profitability - Healthy margins',
            $percentage >= 5 => 'Average profitability - Room for improvement',
            default => 'Low profitability - Focus on cost reduction or revenue enhancement',
        };
    }

    private function interpretAssetTurnover(float $turnover): string
    {
        return match (true) {
            $turnover >= 2.0 => 'Excellent - Very efficient asset utilization',
            $turnover >= 1.0 => 'Good - Efficient use of assets to generate revenue',
            $turnover >= 0.5 => 'Average - Moderate asset efficiency',
            default => 'Low - Consider improving asset utilization or divesting underperforming assets',
        };
    }

    private function interpretEquityMultiplier(float $multiplier): string
    {
        return match (true) {
            $multiplier >= 3.0 => 'High leverage - Significant use of debt financing, higher risk',
            $multiplier >= 2.0 => 'Moderate leverage - Balanced use of debt and equity',
            $multiplier >= 1.5 => 'Low leverage - Conservative capital structure',
            default => 'Very low leverage - Primarily equity-financed, may be missing leverage benefits',
        };
    }
}
