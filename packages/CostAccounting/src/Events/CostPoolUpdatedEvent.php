<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Events;

/**
 * Cost Pool Updated Event
 * 
 * Dispatched when a cost pool is updated.
 */
class CostPoolUpdatedEvent
{
    public function __construct(
        public readonly string $poolId,
        public readonly string $code,
        public readonly string $name,
        public readonly float $previousAmount,
        public readonly float $newAmount,
        public readonly string $tenantId,
        public readonly \DateTimeImmutable $occurredAt
    ) {}

    /**
     * Get the amount change
     */
    public function getAmountChange(): float
    {
        return $this->newAmount - $this->previousAmount;
    }

    /**
     * Check if this is an increase
     */
    public function isIncrease(): bool
    {
        return $this->getAmountChange() > 0;
    }

    /**
     * Check if this is a decrease
     */
    public function isDecrease(): bool
    {
        return $this->getAmountChange() < 0;
    }
}
