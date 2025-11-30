<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Enums\ForecastConfidence;
use Nexus\Manufacturing\ValueObjects\DemandForecast;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;

/**
 * Forecast Provider interface.
 *
 * Defines the contract for ML-powered demand forecasting.
 * Implementations integrate with Nexus\MachineLearning package.
 */
interface ForecastProviderInterface
{
    /**
     * Generate ML-powered forecast.
     *
     * @param string $productId Product to forecast
     * @param \DateTimeImmutable $startDate Forecast start date
     * @param \DateTimeImmutable $endDate Forecast end date
     * @param array<string, mixed> $features Additional features for ML model
     * @return DemandForecast Forecast with ML confidence
     *
     * @throws \Nexus\Manufacturing\Exceptions\ForecastUnavailableException If ML service unavailable
     */
    public function predict(
        string $productId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        array $features = []
    ): DemandForecast;

    /**
     * Generate forecast using planning horizon.
     *
     * @param string $productId Product to forecast
     * @param PlanningHorizon $horizon Planning horizon
     * @return DemandForecast Forecast with ML confidence
     *
     * @throws \Nexus\Manufacturing\Exceptions\ForecastUnavailableException If ML service unavailable
     */
    public function generateForecast(string $productId, PlanningHorizon $horizon): DemandForecast;

    /**
     * Check if the provider is available.
     */
    public function isAvailable(): bool;

    /**
     * Check if the ML provider is healthy.
     */
    public function isHealthy(): bool;

    /**
     * Get provider health status.
     *
     * @return array{healthy: bool, latency: float, lastCheck: \DateTimeImmutable}
     */
    public function getHealth(): array;

    /**
     * Get supported forecast horizons.
     *
     * @return array{min: int, max: int, recommended: int} Days
     */
    public function getSupportedHorizons(): array;

    /**
     * Get the model version being used.
     */
    public function getModelVersion(): string;

    /**
     * Get the model's confidence level for a specific product.
     *
     * @param string $productId Product to assess
     * @return ForecastConfidence Confidence level
     */
    public function getModelConfidence(string $productId): ForecastConfidence;
}
