<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Contracts;

use Nexus\FinancialRatios\ValueObjects\RatioResult;

/**
 * Contract for cash flow ratio calculations.
 */
interface CashFlowRatioInterface
{
    /**
     * Calculate operating cash flow ratio (Operating CF / Current Liabilities).
     */
    public function operatingCashFlowRatio(float $operatingCashFlow, float $currentLiabilities): RatioResult;

    /**
     * Calculate free cash flow (Operating CF - CapEx).
     */
    public function freeCashFlow(float $operatingCashFlow, float $capitalExpenditures): float;

    /**
     * Calculate cash flow margin (Operating CF / Revenue).
     */
    public function cashFlowMargin(float $operatingCashFlow, float $revenue): RatioResult;

    /**
     * Calculate cash flow to debt ratio (Operating CF / Total Debt).
     */
    public function cashFlowToDebt(float $operatingCashFlow, float $totalDebt): RatioResult;

    /**
     * Calculate cash flow coverage ratio (Operating CF / Debt Service).
     */
    public function cashFlowCoverage(float $operatingCashFlow, float $debtService): RatioResult;

    /**
     * Calculate capital expenditure ratio (Operating CF / CapEx).
     */
    public function capitalExpenditureRatio(float $operatingCashFlow, float $capitalExpenditures): RatioResult;
}
