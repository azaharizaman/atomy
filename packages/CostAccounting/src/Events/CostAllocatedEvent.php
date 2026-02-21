<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Events;

/**
 * Cost Allocated Event
 * 
 * Dispatched when costs are allocated from a pool to cost centers.
 */
class CostAllocatedEvent
{
    /**
     * @param array<string, float> $allocations Keyed by cost center ID with allocated amount
     */
    public function __construct(
        public readonly string $poolId,
        public readonly string $poolName,
        public readonly array $allocations,
        public readonly float $totalAllocated,
        public readonly string $periodId,
        public readonly string $tenantId,
        public readonly \DateTimeImmutable $occurredAt
    ) {}

    /**
     * Get the number of allocations made
     */
    public function getAllocationCount(): int
    {
        return count($this->allocations);
    }

    /**
     * Get allocation for a specific cost center
     */
    public function getAllocationForCostCenter(string $costCenterId): ?float
    {
        return $this->allocations[$costCenterId] ?? null;
    }
}
