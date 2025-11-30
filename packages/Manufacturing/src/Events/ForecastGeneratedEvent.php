<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Events;

use Nexus\Manufacturing\Enums\ForecastConfidence;

/**
 * Event raised when a demand forecast is generated.
 */
final readonly class ForecastGeneratedEvent
{
    /**
     * @param array<string, float> $periodQuantities
     * @param array<int, float>|null $seasonalFactors
     */
    public function __construct(
        public string $forecastId,
        public string $productId,
        public \DateTimeImmutable $horizonStart,
        public \DateTimeImmutable $horizonEnd,
        public float $totalQuantity,
        public array $periodQuantities,
        public ForecastConfidence $confidence,
        public string $source,
        public ?string $modelVersion,
        public ?string $algorithm,
        public ?array $seasonalFactors,
        public ?string $trendIndicator,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'event' => 'forecast.generated',
            'forecastId' => $this->forecastId,
            'productId' => $this->productId,
            'horizonStart' => $this->horizonStart->format('c'),
            'horizonEnd' => $this->horizonEnd->format('c'),
            'totalQuantity' => $this->totalQuantity,
            'periodQuantities' => $this->periodQuantities,
            'confidence' => $this->confidence->value,
            'source' => $this->source,
            'modelVersion' => $this->modelVersion,
            'algorithm' => $this->algorithm,
            'seasonalFactors' => $this->seasonalFactors,
            'trendIndicator' => $this->trendIndicator,
            'occurredAt' => $this->occurredAt->format('c'),
        ];
    }

    /**
     * Check if this is an ML-generated forecast.
     */
    public function isMLGenerated(): bool
    {
        return in_array($this->source, ['ml', 'machine_learning', 'ai'], true);
    }

    /**
     * Check if this is a high confidence forecast.
     */
    public function isHighConfidence(): bool
    {
        return in_array($this->confidence, [
            ForecastConfidence::HIGH,
            ForecastConfidence::VERY_HIGH,
        ], true);
    }

    /**
     * Get number of periods in forecast.
     */
    public function getPeriodCount(): int
    {
        return count($this->periodQuantities);
    }
}
