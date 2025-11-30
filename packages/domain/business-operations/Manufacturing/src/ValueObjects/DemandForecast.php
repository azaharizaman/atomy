<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\ValueObjects;

use Nexus\Manufacturing\Enums\ForecastConfidence;

/**
 * Demand Forecast value object.
 *
 * Represents a demand forecast with ML or fallback source.
 */
final readonly class DemandForecast
{
    /**
     * @param string $productId Product forecasted
     * @param \DateTimeImmutable $startDate Forecast period start
     * @param \DateTimeImmutable $endDate Forecast period end
     * @param float $quantity Forecasted quantity
     * @param ForecastConfidence $confidence Confidence level
     * @param string $source Source: 'ml', 'historical', 'manual'
     * @param string|null $modelVersion ML model version if applicable
     * @param float|null $lowerBound Lower confidence interval bound
     * @param float|null $upperBound Upper confidence interval bound
     * @param array<string, float>|null $periodBreakdown Quantity by sub-period
     * @param array<string, mixed> $metadata Additional metadata
     * @param \DateTimeImmutable|null $calculatedAt Calculation timestamp
     */
    public function __construct(
        public string $productId,
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
        public float $quantity,
        public ForecastConfidence $confidence,
        public string $source,
        public ?string $modelVersion = null,
        public ?float $lowerBound = null,
        public ?float $upperBound = null,
        public ?array $periodBreakdown = null,
        public array $metadata = [],
        public ?\DateTimeImmutable $calculatedAt = null,
    ) {
        if ($this->quantity < 0) {
            throw new \InvalidArgumentException('Forecast quantity cannot be negative');
        }
        if ($this->endDate <= $this->startDate) {
            throw new \InvalidArgumentException('End date must be after start date');
        }
        if (!in_array($this->source, ['ml', 'historical', 'manual'], true)) {
            throw new \InvalidArgumentException('Source must be ml, historical, or manual');
        }
    }

    /**
     * Check if this is an ML-based forecast.
     */
    public function isMlBased(): bool
    {
        return $this->source === 'ml';
    }

    /**
     * Check if this is a fallback forecast.
     */
    public function isFallback(): bool
    {
        return $this->source === 'historical';
    }

    /**
     * Check if confidence is high enough for automated planning.
     */
    public function isHighConfidence(): bool
    {
        return $this->confidence->getScore() >= 0.7;
    }

    /**
     * Check if forecast needs manual review.
     */
    public function needsReview(): bool
    {
        return $this->confidence->requiresReview();
    }

    /**
     * Get forecast horizon in days.
     */
    public function getHorizonDays(): int
    {
        return (int) $this->startDate->diff($this->endDate)->days;
    }

    /**
     * Get confidence interval range.
     */
    public function getConfidenceRange(): ?float
    {
        if ($this->lowerBound === null || $this->upperBound === null) {
            return null;
        }
        return $this->upperBound - $this->lowerBound;
    }

    /**
     * Get daily average forecast.
     */
    public function getDailyAverage(): float
    {
        $days = $this->getHorizonDays();
        if ($days <= 0) {
            return $this->quantity;
        }
        return $this->quantity / $days;
    }

    /**
     * Get weekly average forecast.
     */
    public function getWeeklyAverage(): float
    {
        return $this->getDailyAverage() * 7;
    }

    /**
     * Get recommended safety stock multiplier.
     */
    public function getSafetyStockMultiplier(): float
    {
        return $this->confidence->getSafetyStockMultiplier();
    }

    /**
     * Create a copy with adjusted quantity.
     */
    public function withQuantity(float $quantity): self
    {
        return new self(
            productId: $this->productId,
            startDate: $this->startDate,
            endDate: $this->endDate,
            quantity: $quantity,
            confidence: ForecastConfidence::MEDIUM, // Manual override
            source: 'manual',
            modelVersion: null,
            lowerBound: null,
            upperBound: null,
            periodBreakdown: null,
            metadata: [...$this->metadata, 'originalQuantity' => $this->quantity],
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'productId' => $this->productId,
            'startDate' => $this->startDate->format('Y-m-d'),
            'endDate' => $this->endDate->format('Y-m-d'),
            'quantity' => $this->quantity,
            'confidence' => $this->confidence->value,
            'confidenceScore' => $this->confidence->getScore(),
            'source' => $this->source,
            'modelVersion' => $this->modelVersion,
            'lowerBound' => $this->lowerBound,
            'upperBound' => $this->upperBound,
            'periodBreakdown' => $this->periodBreakdown,
            'metadata' => $this->metadata,
            'calculatedAt' => $this->calculatedAt?->format('Y-m-d H:i:s'),
            'summary' => [
                'horizonDays' => $this->getHorizonDays(),
                'dailyAverage' => $this->getDailyAverage(),
                'weeklyAverage' => $this->getWeeklyAverage(),
                'isMlBased' => $this->isMlBased(),
                'isFallback' => $this->isFallback(),
                'needsReview' => $this->needsReview(),
                'safetyStockMultiplier' => $this->getSafetyStockMultiplier(),
            ],
        ];
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['productId'],
            startDate: new \DateTimeImmutable($data['startDate']),
            endDate: new \DateTimeImmutable($data['endDate']),
            quantity: (float) $data['quantity'],
            confidence: ForecastConfidence::from($data['confidence']),
            source: $data['source'],
            modelVersion: $data['modelVersion'] ?? null,
            lowerBound: isset($data['lowerBound']) ? (float) $data['lowerBound'] : null,
            upperBound: isset($data['upperBound']) ? (float) $data['upperBound'] : null,
            periodBreakdown: $data['periodBreakdown'] ?? null,
            metadata: $data['metadata'] ?? [],
            calculatedAt: isset($data['calculatedAt'])
                ? new \DateTimeImmutable($data['calculatedAt'])
                : null,
        );
    }

    /**
     * Create an ML-based forecast.
     */
    public static function fromMl(
        string $productId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        float $quantity,
        float $confidenceScore,
        string $modelVersion,
        ?float $lowerBound = null,
        ?float $upperBound = null
    ): self {
        return new self(
            productId: $productId,
            startDate: $startDate,
            endDate: $endDate,
            quantity: $quantity,
            confidence: ForecastConfidence::fromScore($confidenceScore),
            source: 'ml',
            modelVersion: $modelVersion,
            lowerBound: $lowerBound,
            upperBound: $upperBound,
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create a historical fallback forecast.
     */
    public static function fromHistorical(
        string $productId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        float $quantity,
        string $method = 'moving_average'
    ): self {
        return new self(
            productId: $productId,
            startDate: $startDate,
            endDate: $endDate,
            quantity: $quantity,
            confidence: ForecastConfidence::FALLBACK,
            source: 'historical',
            metadata: ['method' => $method],
            calculatedAt: new \DateTimeImmutable(),
        );
    }
}
