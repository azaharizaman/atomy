<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Services;

use Nexus\AccountVarianceAnalysis\ValueObjects\ForecastVariance;

/**
 * Pure logic for rolling forecast calculations.
 */
final readonly class RollingForecastCalculator
{
    public function __construct(
        private TrendAnalyzer $trendAnalyzer = new TrendAnalyzer(),
    ) {}

    /**
     * Generate rolling forecast based on historical data.
     *
     * @param array<float> $historicalValues
     * @param int $periodsAhead
     * @return array<float>
     */
    public function forecast(array $historicalValues, int $periodsAhead): array
    {
        if (count($historicalValues) < 3) {
            return array_fill(0, $periodsAhead, end($historicalValues) ?: 0.0);
        }

        $trend = $this->trendAnalyzer->analyze($historicalValues);
        $forecasts = [];

        $n = count($historicalValues);
        for ($i = 1; $i <= $periodsAhead; $i++) {
            $forecasts[] = $trend->getForecast() !== null
                ? $trend->getForecast() + ($trend->getSlope() * ($i - 1))
                : end($historicalValues);
        }

        return $forecasts;
    }

    /**
     * Calculate forecast accuracy.
     *
     * @param float $forecast
     * @param float $actual
     * @return float Accuracy as percentage (100 = perfect)
     */
    public function calculateAccuracy(float $forecast, float $actual): float
    {
        if ($actual == 0) {
            return $forecast == 0 ? 100.0 : 0.0;
        }

        $error = abs($forecast - $actual);
        $accuracy = (1 - ($error / abs($actual))) * 100;

        return max(0, min(100, $accuracy));
    }

    /**
     * Create forecast variance record.
     */
    public function createForecastVariance(
        string $periodId,
        float $forecast,
        float $actual
    ): ForecastVariance {
        return new ForecastVariance(
            periodId: $periodId,
            forecast: $forecast,
            actual: $actual,
            varianceAmount: $actual - $forecast,
            forecastAccuracy: $this->calculateAccuracy($forecast, $actual)
        );
    }
}
