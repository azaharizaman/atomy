<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Enums;

/**
 * Cost Pool Status enum
 * 
 * Represents the lifecycle status of a cost pool.
 */
enum CostPoolStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }

    /**
     * Check if pool can accept allocations
     */
    public function canAcceptAllocations(): bool
    {
        return $this === self::Active;
    }

    /**
     * Check if pool is operational
     */
    public function isOperational(): bool
    {
        return $this === self::Active;
    }
}
