<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Contracts;

use Nexus\FinancialRatios\ValueObjects\RatioResult;

/**
 * Contract for profitability ratio calculations.
 */
interface ProfitabilityRatioInterface
{
    /**
     * Calculate gross profit margin (Gross Profit / Revenue).
     */
    public function grossProfitMargin(float $grossProfit, float $revenue): RatioResult;

    /**
     * Calculate operating profit margin (Operating Income / Revenue).
     */
    public function operatingProfitMargin(float $operatingIncome, float $revenue): RatioResult;

    /**
     * Calculate net profit margin (Net Income / Revenue).
     */
    public function netProfitMargin(float $netIncome, float $revenue): RatioResult;

    /**
     * Calculate return on assets (Net Income / Total Assets).
     */
    public function returnOnAssets(float $netIncome, float $totalAssets): RatioResult;

    /**
     * Calculate return on equity (Net Income / Shareholders' Equity).
     */
    public function returnOnEquity(float $netIncome, float $shareholdersEquity): RatioResult;

    /**
     * Calculate return on capital employed (EBIT / Capital Employed).
     */
    public function returnOnCapitalEmployed(float $ebit, float $capitalEmployed): RatioResult;
}
