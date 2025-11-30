<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\DemandForecastInterface;
use Nexus\Manufacturing\Contracts\ForecastProviderInterface;
use Nexus\Manufacturing\Contracts\ForecastFallbackInterface;
use Nexus\Manufacturing\Enums\ForecastConfidence;
use Nexus\Manufacturing\Exceptions\ForecastUnavailableException;
use Nexus\Manufacturing\ValueObjects\DemandForecast;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;
use Psr\Log\LoggerInterface;

/**
 * Demand Forecaster implementation.
 *
 * Integrates with Nexus\MachineLearning for AI-powered demand forecasting
 * with graceful fallback to historical data when ML is unavailable.
 */
final class DemandForecaster implements DemandForecastInterface
{
    /**
     * @var array{enabled: bool, method: string, periods: int, publishEvent: bool}
     */
    private array $fallbackConfig = [
        'enabled' => true,
        'method' => 'historical_average',
        'periods' => 12,
        'publishEvent' => true,
    ];

    public function __construct(
        private readonly ?ForecastProviderInterface $mlProvider = null,
        private readonly ?ForecastFallbackInterface $fallbackProvider = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function forecast(
        string $productId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): DemandForecast {
        $horizon = new PlanningHorizon(
            startDate: $startDate,
            endDate: $endDate,
        );

        // Try ML provider first
        if ($this->mlProvider !== null) {
            try {
                $forecast = $this->mlProvider->generateForecast($productId, $horizon);

                $this->logger?->info('ML forecast generated', [
                    'productId' => $productId,
                    'horizon' => $horizon->getTotalDays() . ' days',
                    'confidence' => $forecast->confidence->value,
                ]);

                return $forecast;
            } catch (\Exception $e) {
                $this->logger?->warning('ML forecast failed, falling back', [
                    'productId' => $productId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fall back to historical data
        if ($this->fallbackProvider !== null) {
            try {
                $forecast = $this->fallbackProvider->generateFromHistory($productId, $horizon);

                $this->logger?->info('Historical fallback forecast generated', [
                    'productId' => $productId,
                    'horizon' => $horizon->getTotalDays() . ' days',
                    'confidence' => $forecast->confidence->value,
                ]);

                return $forecast;
            } catch (\Exception $e) {
                $this->logger?->error('Fallback forecast also failed', [
                    'productId' => $productId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw ForecastUnavailableException::noProviderAvailable($productId);
    }

    /**
     * {@inheritdoc}
     */
    public function forecastMultiple(
        array $productIds,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        $forecasts = [];
        $failed = [];

        foreach ($productIds as $productId) {
            try {
                $forecasts[$productId] = $this->forecast($productId, $startDate, $endDate);
            } catch (\Exception $e) {
                $failed[$productId] = $e->getMessage();
            }
        }

        if (!empty($failed)) {
            $this->logger?->warning('Some forecasts failed', [
                'totalRequested' => count($productIds),
                'succeeded' => count($forecasts),
                'failed' => count($failed),
                'failedProducts' => array_keys($failed),
            ]);
        }

        return $forecasts;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfidenceLevel(string $productId): ForecastConfidence
    {
        // Determine confidence based on data availability
        $hasMLProvider = $this->mlProvider !== null;
        $hasFallback = $this->fallbackProvider !== null;

        if (!$hasMLProvider && !$hasFallback) {
            return ForecastConfidence::VERY_LOW;
        }

        if ($hasMLProvider) {
            try {
                return $this->mlProvider->getModelConfidence($productId);
            } catch (\Exception) {
                // ML provider can't assess confidence
            }
        }

        if ($hasFallback) {
            try {
                return $this->fallbackProvider->getHistoricalConfidence($productId);
            } catch (\Exception) {
                // Fallback can't assess confidence
            }
        }

        return ForecastConfidence::LOW;
    }

    /**
     * {@inheritdoc}
     */
    public function isMlAvailable(): bool
    {
        if ($this->mlProvider === null) {
            return false;
        }

        try {
            return $this->mlProvider->isHealthy();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHistoricalDemand(
        string $productId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        if ($this->fallbackProvider === null) {
            return [];
        }

        return $this->fallbackProvider->getHistoricalData($productId, $startDate, $endDate);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateSeasonalFactors(string $productId): array
    {
        if ($this->fallbackProvider === null) {
            return array_fill(1, 12, 1.0); // No seasonality
        }

        return $this->fallbackProvider->calculateSeasonality($productId);
    }

    /**
     * {@inheritdoc}
     */
    public function adjustForecast(DemandForecast $forecast, array $adjustments): DemandForecast
    {
        $adjustedQuantities = $forecast->periodQuantities;
        $notes = $forecast->notes ?? [];
        $notes[] = 'Manually adjusted';

        foreach ($adjustments as $period => $adjustment) {
            if (isset($adjustedQuantities[$period])) {
                $originalQty = $adjustedQuantities[$period];

                if (isset($adjustment['quantity'])) {
                    $adjustedQuantities[$period] = $adjustment['quantity'];
                } elseif (isset($adjustment['multiplier'])) {
                    $adjustedQuantities[$period] *= $adjustment['multiplier'];
                } elseif (isset($adjustment['delta'])) {
                    $adjustedQuantities[$period] += $adjustment['delta'];
                }

                $notes[] = "Period {$period}: adjusted from {$originalQty} to {$adjustedQuantities[$period]}";
            }
        }

        // Downgrade confidence after manual adjustment
        $newConfidence = match ($forecast->confidence) {
            ForecastConfidence::VERY_HIGH => ForecastConfidence::HIGH,
            ForecastConfidence::HIGH => ForecastConfidence::MEDIUM,
            ForecastConfidence::MEDIUM => ForecastConfidence::LOW,
            default => ForecastConfidence::VERY_LOW,
        };

        return new DemandForecast(
            productId: $forecast->productId,
            horizon: $forecast->horizon,
            totalQuantity: array_sum($adjustedQuantities),
            periodQuantities: $adjustedQuantities,
            confidence: $newConfidence,
            source: 'manual_adjustment',
            modelVersion: $forecast->modelVersion,
            generatedAt: new \DateTimeImmutable(),
            seasonalFactors: $forecast->seasonalFactors,
            trendIndicator: $forecast->trendIndicator,
            notes: $notes,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function compareForecastToActual(
        string $productId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): array {
        if ($this->fallbackProvider === null) {
            return [];
        }

        // Get historical forecasts for the period
        // This would require storing historical forecasts
        $historicalForecasts = [];

        // Get actual demand from history
        $actualDemand = $this->fallbackProvider->getHistoricalData($productId, $periodStart, $periodEnd);

        // Calculate accuracy metrics
        $totalForecast = 0.0;
        $totalActual = array_sum(array_column($actualDemand, 'quantity'));

        foreach ($historicalForecasts as $forecast) {
            $totalForecast += $forecast['quantity'];
        }

        $mape = $totalActual > 0
            ? abs($totalForecast - $totalActual) / $totalActual * 100
            : 0;

        $bias = $totalForecast - $totalActual;

        return [
            'productId' => $productId,
            'periodStart' => $periodStart->format('Y-m-d'),
            'periodEnd' => $periodEnd->format('Y-m-d'),
            'forecastedQuantity' => $totalForecast,
            'actualQuantity' => $totalActual,
            'variance' => $bias,
            'variancePercentage' => $totalActual > 0 ? ($bias / $totalActual) * 100 : 0,
            'mape' => $mape, // Mean Absolute Percentage Error
            'accuracy' => 100 - $mape,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedAlgorithms(): array
    {
        $algorithms = [];

        if ($this->mlProvider !== null) {
            $algorithms = array_merge($algorithms, $this->mlProvider->getSupportedAlgorithms());
        }

        if ($this->fallbackProvider !== null) {
            $algorithms[] = 'historical_average';
            $algorithms[] = 'seasonal_decomposition';
            $algorithms[] = 'exponential_smoothing';
        }

        return array_unique($algorithms);
    }

    /**
     * {@inheritdoc}
     */
    public function forecastByCategory(
        string $categoryId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): DemandForecast {
        $horizon = new PlanningHorizon(
            startDate: $startDate,
            endDate: $endDate,
        );

        // Try ML provider first for category-level forecast
        if ($this->mlProvider !== null) {
            try {
                $forecast = $this->mlProvider->generateCategoryForecast($categoryId, $horizon);

                $this->logger?->info('ML category forecast generated', [
                    'categoryId' => $categoryId,
                    'horizon' => $horizon->getTotalDays() . ' days',
                    'confidence' => $forecast->confidence->value,
                ]);

                return $forecast;
            } catch (\Exception $e) {
                $this->logger?->warning('ML category forecast failed, falling back', [
                    'categoryId' => $categoryId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fall back to aggregating individual product forecasts
        if ($this->fallbackProvider !== null) {
            try {
                $forecast = $this->fallbackProvider->generateCategoryFromHistory($categoryId, $horizon);

                $this->logger?->info('Historical fallback category forecast generated', [
                    'categoryId' => $categoryId,
                    'horizon' => $horizon->getTotalDays() . ' days',
                    'confidence' => $forecast->confidence->value,
                ]);

                return $forecast;
            } catch (\Exception $e) {
                $this->logger?->error('Fallback category forecast also failed', [
                    'categoryId' => $categoryId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw ForecastUnavailableException::noProviderAvailable($categoryId);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccuracyMetrics(string $productId, int $periods = 12): array
    {
        if ($this->fallbackProvider === null) {
            return [
                'mape' => 0.0,
                'rmse' => 0.0,
                'bias' => 0.0,
            ];
        }

        // Get historical forecasts and actuals
        $endDate = new \DateTimeImmutable();
        $startDate = $endDate->modify("-{$periods} months");

        $actualDemand = $this->fallbackProvider->getHistoricalData($productId, $startDate, $endDate);

        if (empty($actualDemand)) {
            return [
                'mape' => 0.0,
                'rmse' => 0.0,
                'bias' => 0.0,
            ];
        }

        // Calculate accuracy metrics
        $totalForecast = 0.0;
        $totalActual = 0.0;
        $sumAbsoluteError = 0.0;
        $sumSquaredError = 0.0;
        $count = 0;

        foreach ($actualDemand as $period) {
            $actual = (float) ($period['quantity'] ?? 0);
            $forecast = (float) ($period['forecasted'] ?? $actual);

            $totalActual += $actual;
            $totalForecast += $forecast;

            if ($actual > 0) {
                $error = $forecast - $actual;
                $sumAbsoluteError += abs($error / $actual);
                $sumSquaredError += $error ** 2;
                $count++;
            }
        }

        $mape = $count > 0 ? ($sumAbsoluteError / $count) * 100 : 0.0;
        $rmse = $count > 0 ? sqrt($sumSquaredError / $count) : 0.0;
        $bias = $totalForecast - $totalActual;

        return [
            'mape' => round($mape, 2),
            'rmse' => round($rmse, 2),
            'bias' => round($bias, 2),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function recordActual(
        string $productId,
        \DateTimeImmutable $period,
        float $actualDemand
    ): void {
        if ($this->fallbackProvider !== null) {
            $this->fallbackProvider->recordActualDemand($productId, $period, $actualDemand);
        }

        $this->logger?->info('Actual demand recorded', [
            'productId' => $productId,
            'period' => $period->format('Y-m-d'),
            'actualDemand' => $actualDemand,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setFallbackConfig(array $config): void
    {
        $this->fallbackConfig = array_merge($this->fallbackConfig, $config);

        $this->logger?->info('Fallback configuration updated', [
            'config' => $this->fallbackConfig,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFallbackConfig(): array
    {
        return $this->fallbackConfig;
    }
}
