<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\ValueObjects\DemandForecast;

/**
 * Demand Forecast interface.
 *
 * Provides ML-powered demand forecasting with graceful fallback
 * to historical data when ML service is unavailable.
 */
interface DemandForecastInterface
{
    /**
     * Get demand forecast for a product.
     *
     * Attempts ML-powered prediction first, falls back to historical
     * if ML service is unavailable.
     *
     * @param string $productId Product to forecast
     * @param \DateTimeImmutable $startDate Forecast start date
     * @param \DateTimeImmutable $endDate Forecast end date
     * @return DemandForecast Forecast result with confidence and source info
     */
    public function forecast(
        string $productId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): DemandForecast;

    /**
     * Get demand forecast for multiple products.
     *
     * @param array<string> $productIds Products to forecast
     * @param \DateTimeImmutable $startDate Forecast start date
     * @param \DateTimeImmutable $endDate Forecast end date
     * @return array<string, DemandForecast> Product ID => forecast map
     */
    public function forecastMultiple(
        array $productIds,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;

    /**
     * Get aggregate demand forecast by category.
     *
     * @param string $categoryId Product category
     * @param \DateTimeImmutable $startDate Forecast start date
     * @param \DateTimeImmutable $endDate Forecast end date
     * @return DemandForecast Aggregated forecast
     */
    public function forecastByCategory(
        string $categoryId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): DemandForecast;

    /**
     * Check if ML forecasting is available.
     */
    public function isMlAvailable(): bool;

    /**
     * Get forecast accuracy metrics.
     *
     * Compares previous forecasts with actual demand.
     *
     * @param string $productId Product to analyze
     * @param int $periods Number of past periods to analyze
     * @return array{mape: float, rmse: float, bias: float}
     */
    public function getAccuracyMetrics(string $productId, int $periods = 12): array;

    /**
     * Record actual demand (for accuracy tracking).
     *
     * @param string $productId Product
     * @param \DateTimeImmutable $period Period date
     * @param float $actualDemand Actual demand quantity
     */
    public function recordActual(
        string $productId,
        \DateTimeImmutable $period,
        float $actualDemand
    ): void;

    /**
     * Set fallback configuration.
     *
     * @param array{
     *     enabled: bool,
     *     method: string,
     *     periods: int,
     *     publishEvent: bool
     * } $config
     */
    public function setFallbackConfig(array $config): void;

    /**
     * Get current fallback configuration.
     *
     * @return array{enabled: bool, method: string, periods: int, publishEvent: bool}
     */
    public function getFallbackConfig(): array;
}
