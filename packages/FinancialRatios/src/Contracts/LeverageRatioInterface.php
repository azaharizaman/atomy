<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Contracts;

use Nexus\FinancialRatios\ValueObjects\RatioResult;

/**
 * Contract for leverage ratio calculations.
 */
interface LeverageRatioInterface
{
    /**
     * Calculate debt ratio (Total Liabilities / Total Assets).
     */
    public function debtRatio(float $totalLiabilities, float $totalAssets): RatioResult;

    /**
     * Calculate debt-to-equity ratio (Total Liabilities / Shareholders' Equity).
     */
    public function debtToEquityRatio(float $totalLiabilities, float $shareholdersEquity): RatioResult;

    /**
     * Calculate equity ratio (Shareholders' Equity / Total Assets).
     */
    public function equityRatio(float $shareholdersEquity, float $totalAssets): RatioResult;

    /**
     * Calculate interest coverage ratio (EBIT / Interest Expense).
     */
    public function interestCoverageRatio(float $ebit, float $interestExpense): RatioResult;

    /**
     * Calculate debt service coverage ratio (Net Operating Income / Debt Service).
     */
    public function debtServiceCoverageRatio(float $netOperatingIncome, float $debtService): RatioResult;
}
