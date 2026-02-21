<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Events;

/**
 * Cost Revaluation Executed Event
 * 
 * Dispatched when cost revaluation is executed.
 */
class CostRevaluationExecutedEvent
{
    public function __construct(
        public readonly string $productId,
        public readonly string $periodId,
        public readonly float $previousCost,
        public readonly float $newCost,
        public readonly float $varianceAmount,
        public readonly float $variancePercentage,
        public readonly string $reason,
        public readonly string $tenantId,
        public readonly \DateTimeImmutable $occurredAt
    ) {}

    /**
     * Check if this is a cost increase
     */
    public function isIncrease(): bool
    {
        return $this->varianceAmount > 0;
    }

    /**
     * Check if this is a cost decrease
     */
    public function isDecrease(): bool
    {
        return $this->varianceAmount < 0;
    }

    /**
     * Get the variance details
     */
    public function getVarianceDetails(): array
    {
        return [
            'previous_cost' => $this->previousCost,
            'new_cost' => $this->newCost,
            'variance_amount' => $this->varianceAmount,
            'variance_percentage' => $this->variancePercentage,
            'reason' => $this->reason,
        ];
    }
}
