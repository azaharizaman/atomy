<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Events;

/**
 * Product Cost Calculated Event
 * 
 * Dispatched when product cost is calculated.
 */
class ProductCostCalculatedEvent
{
    public function __construct(
        public readonly string $productId,
        public readonly string $costCenterId,
        public readonly string $periodId,
        public readonly float $materialCost,
        public readonly float $laborCost,
        public readonly float $overheadCost,
        public readonly float $totalCost,
        public readonly float $unitCost,
        public readonly string $costType, // actual or standard
        public readonly string $tenantId,
        public readonly \DateTimeImmutable $occurredAt
    ) {}

    /**
     * Get the cost breakdown
     */
    public function getCostBreakdown(): array
    {
        return [
            'material' => $this->materialCost,
            'labor' => $this->laborCost,
            'overhead' => $this->overheadCost,
            'total' => $this->totalCost,
            'unit' => $this->unitCost,
        ];
    }

    /**
     * Check if this is a standard cost
     */
    public function isStandardCost(): bool
    {
        return $this->costType === 'standard';
    }

    /**
     * Check if this is an actual cost
     */
    public function isActualCost(): bool
    {
        return $this->costType === 'actual';
    }
}
