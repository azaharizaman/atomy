<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Services;

/**
 * Pure calculation logic for retained earnings.
 */
final readonly class RetainedEarningsCalculator
{
    /**
     * Calculate retained earnings.
     *
     * @param float $openingBalance
     * @param float $netIncome
     * @param float $dividendsDeclared
     * @param float $priorPeriodAdjustments
     * @return float
     */
    public function calculate(
        float $openingBalance,
        float $netIncome,
        float $dividendsDeclared = 0.0,
        float $priorPeriodAdjustments = 0.0
    ): float {
        return $openingBalance
            + $netIncome
            - $dividendsDeclared
            + $priorPeriodAdjustments;
    }

    /**
     * Calculate net income from revenue and expenses.
     *
     * @param float $totalRevenue
     * @param float $totalExpenses
     * @return float
     */
    public function calculateNetIncome(float $totalRevenue, float $totalExpenses): float
    {
        return $totalRevenue - $totalExpenses;
    }
}
