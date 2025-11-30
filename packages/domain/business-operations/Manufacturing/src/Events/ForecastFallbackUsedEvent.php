<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Events;

use Nexus\Manufacturing\Enums\ForecastConfidence;

/**
 * Event raised when forecast falls back to historical data (ML unavailable).
 *
 * This event is crucial for monitoring ML health and triggering alerts
 * when the ML forecasting system is degraded.
 */
final readonly class ForecastFallbackUsedEvent
{
    public function __construct(
        public string $forecastId,
        public string $productId,
        public string $fallbackReason,
        public ?string $mlErrorMessage,
        public ?string $mlErrorCode,
        public string $fallbackMethod,
        public ForecastConfidence $fallbackConfidence,
        public int $historicalDaysUsed,
        public float $generatedQuantity,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'event' => 'forecast.fallback_used',
            'forecastId' => $this->forecastId,
            'productId' => $this->productId,
            'fallbackReason' => $this->fallbackReason,
            'mlErrorMessage' => $this->mlErrorMessage,
            'mlErrorCode' => $this->mlErrorCode,
            'fallbackMethod' => $this->fallbackMethod,
            'fallbackConfidence' => $this->fallbackConfidence->value,
            'historicalDaysUsed' => $this->historicalDaysUsed,
            'generatedQuantity' => $this->generatedQuantity,
            'occurredAt' => $this->occurredAt->format('c'),
        ];
    }

    /**
     * Check if fallback was due to ML service being unavailable.
     */
    public function isMLServiceUnavailable(): bool
    {
        return in_array($this->fallbackReason, [
            'ml_service_unavailable',
            'ml_timeout',
            'ml_connection_error',
        ], true);
    }

    /**
     * Check if fallback was due to insufficient training data.
     */
    public function isInsufficientData(): bool
    {
        return in_array($this->fallbackReason, [
            'insufficient_training_data',
            'no_model_available',
            'model_not_trained',
        ], true);
    }

    /**
     * Check if this is a critical fallback that should trigger alerts.
     */
    public function isCritical(): bool
    {
        return $this->isMLServiceUnavailable()
            || $this->fallbackConfidence === ForecastConfidence::VERY_LOW;
    }

    /**
     * Create event for ML service unavailable.
     */
    public static function mlServiceUnavailable(
        string $forecastId,
        string $productId,
        ?string $errorMessage,
        string $fallbackMethod,
        ForecastConfidence $confidence,
        int $historicalDays,
        float $quantity
    ): self {
        return new self(
            forecastId: $forecastId,
            productId: $productId,
            fallbackReason: 'ml_service_unavailable',
            mlErrorMessage: $errorMessage,
            mlErrorCode: 'SERVICE_UNAVAILABLE',
            fallbackMethod: $fallbackMethod,
            fallbackConfidence: $confidence,
            historicalDaysUsed: $historicalDays,
            generatedQuantity: $quantity,
            occurredAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create event for insufficient training data.
     */
    public static function insufficientData(
        string $forecastId,
        string $productId,
        string $fallbackMethod,
        ForecastConfidence $confidence,
        int $historicalDays,
        float $quantity
    ): self {
        return new self(
            forecastId: $forecastId,
            productId: $productId,
            fallbackReason: 'insufficient_training_data',
            mlErrorMessage: 'Product has insufficient historical data for ML model',
            mlErrorCode: 'INSUFFICIENT_DATA',
            fallbackMethod: $fallbackMethod,
            fallbackConfidence: $confidence,
            historicalDaysUsed: $historicalDays,
            generatedQuantity: $quantity,
            occurredAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create event for model not available.
     */
    public static function modelNotAvailable(
        string $forecastId,
        string $productId,
        string $fallbackMethod,
        ForecastConfidence $confidence,
        int $historicalDays,
        float $quantity
    ): self {
        return new self(
            forecastId: $forecastId,
            productId: $productId,
            fallbackReason: 'no_model_available',
            mlErrorMessage: 'No trained model available for this product',
            mlErrorCode: 'MODEL_NOT_FOUND',
            fallbackMethod: $fallbackMethod,
            fallbackConfidence: $confidence,
            historicalDaysUsed: $historicalDays,
            generatedQuantity: $quantity,
            occurredAt: new \DateTimeImmutable(),
        );
    }
}
