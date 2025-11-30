<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

use Nexus\Budget\ValueObjects\BudgetForecast;

/**
 * Budget Forecast service contract
 * 
 * Generates budget forecasts using historical data and AI predictions.
 */
interface BudgetForecastInterface
{
    /**
     * Generate forecast for next period
     * 
     * @param string $budgetId Budget identifier
     * @param string $targetPeriodId Target period identifier
     * @return BudgetForecast
     */
    public function generateForecast(string $budgetId, string $targetPeriodId): BudgetForecast;

    /**
     * Compare forecast accuracy
     * 
     * @param string $forecastId Forecast identifier
     * @param float $actualAmount Actual amount spent
     * @return float Accuracy score (0-100)
     */
    public function compareForecastAccuracy(string $forecastId, float $actualAmount): float;
}
