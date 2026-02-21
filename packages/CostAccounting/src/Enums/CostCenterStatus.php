<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Enums;

/**
 * Cost Center Status enum
 * 
 * Represents the lifecycle status of a cost center.
 */
enum CostCenterStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Pending => 'Pending',
        };
    }

    /**
     * Check if cost center can accept costs
     */
    public function canAcceptCosts(): bool
    {
        return match($this) {
            self::Active => true,
            self::Inactive, self::Pending => false,
        };
    }

    /**
     * Check if cost center can be modified
     */
    public function canModify(): bool
    {
        return match($this) {
            self::Active, self::Pending => true,
            self::Inactive => false,
        };
    }

    /**
     * Check if cost center is operational
     */
    public function isOperational(): bool
    {
        return match($this) {
            self::Active => true,
            self::Inactive, self::Pending => false,
        };
    }
}
