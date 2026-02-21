<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Events;

/**
 * Cost Variance Detected Event
 * 
 * Dispatched when cost variance is detected.
 */
class CostVarianceDetectedEvent
{
    public function __construct(
        public readonly string $productId,
        public readonly string $periodId,
        public readonly float $priceVariance,
        public readonly float $rateVariance,
        public readonly float $efficiencyVariance,
        public readonly float $totalVariance,
        public readonly float $variancePercentage,
        public readonly bool $isFavorable,
        public readonly string $tenantId,
        public readonly \DateTimeImmutable $occurredAt
    ) {}

    /**
     * Get the variance breakdown
     */
    public function getVarianceBreakdown(): array
    {
        return [
            'price' => $this->priceVariance,
            'rate' => $this->rateVariance,
            'efficiency' => $this->efficiencyVariance,
            'total' => $this->totalVariance,
            'percentage' => $this->variancePercentage,
        ];
    }

    /**
     * Check if this variance exceeds the investigation threshold
     */
    public function exceedsThreshold(float $thresholdPercentage): bool
    {
        return abs($this->variancePercentage) > $thresholdPercentage;
    }
}
