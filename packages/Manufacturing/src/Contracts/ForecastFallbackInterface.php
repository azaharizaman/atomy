<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Enums\ForecastConfidence;
use Nexus\Manufacturing\ValueObjects\DemandForecast;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;

/**
 * Forecast Fallback interface.
 *
 * Provides historical demand-based forecasting when ML is unavailable.
 * Implementations calculate forecasts from past demand patterns.
 */
interface ForecastFallbackInterface
{
    /**
     * Calculate forecast from historical data.
     *
     * @param string $productId Product to forecast
     * @param \DateTimeImmutable $startDate Forecast start date
     * @param \DateTimeImmutable $endDate Forecast end date
     * @return DemandForecast Forecast with fallback confidence
     */
    public function calculateFromHistory(
        string $productId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): DemandForecast;

    /**
     * Generate forecast from historical data using planning horizon.
     *
     * @param string $productId Product to forecast
     * @param PlanningHorizon $horizon Planning horizon
     * @return DemandForecast Forecast with fallback confidence
     */
    public function generateFromHistory(string $productId, PlanningHorizon $horizon): DemandForecast;

    /**
     * Get available historical periods for a product.
     *
     * @param string $productId Product to check
     * @return int Number of historical periods available
     */
    public function getAvailableHistory(string $productId): int;

    /**
     * Set the calculation method.
     *
     * @param string $method Method name (moving_average, exponential_smoothing, linear_regression)
     * @param array<string, mixed> $parameters Method-specific parameters
     */
    public function setMethod(string $method, array $parameters = []): void;

    /**
     * Get current calculation method.
     *
     * @return array{method: string, parameters: array<string, mixed>}
     */
    public function getMethod(): array;

    /**
     * Get available calculation methods.
     *
     * @return array<string, string> Method code => description
     */
    public function getAvailableMethods(): array;

    /**
     * Calculate seasonality factors for a product.
     *
     * @param string $productId Product to analyze
     * @param int $periods Number of periods to analyze
     * @return array<int, float> Period index => seasonality factor
     */
    public function calculateSeasonality(string $productId, int $periods = 12): array;

    /**
     * Get the minimum history required for reliable forecasting.
     *
     * @return int Minimum periods
     */
    public function getMinimumHistoryRequired(): int;

    /**
     * Get the historical confidence level for a specific product.
     *
     * @param string $productId Product to assess
     * @return ForecastConfidence Confidence level based on historical data quality
     */
    public function getHistoricalConfidence(string $productId): ForecastConfidence;
}
