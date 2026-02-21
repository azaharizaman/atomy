<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Enums;

/**
 * Cost Allocation Method enum
 * 
 * Defines the method used for cost allocation from
 * cost pools to receiving cost centers.
 */
enum AllocationMethod: string
{
    case Direct = 'direct';
    case StepDown = 'step_down';
    case Reciprocal = 'reciprocal';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::Direct => 'Direct',
            self::StepDown => 'Step-Down',
            self::Reciprocal => 'Reciprocal',
        };
    }

    /**
     * Check if this method requires sequential allocation
     */
    public function requiresSequentialAllocation(): bool
    {
        return match($this) {
            self::StepDown => true,
            self::Direct, self::Reciprocal => false,
        };
    }

    /**
     * Check if this method handles reciprocal relationships
     */
    public function handlesReciprocalRelationships(): bool
    {
        return match($this) {
            self::Reciprocal => true,
            self::Direct, self::StepDown => false,
        };
    }

    /**
     * Get description of the method
     */
    public function description(): string
    {
        return match($this) {
            self::Direct => 
                'Allocates costs directly from source to receiving cost centers based on allocation ratios.',
            self::StepDown => 
                'Allocates costs sequentially from service cost centers to production cost centers.',
            self::Reciprocal => 
                'Allocates costs using simultaneous equations to handle reciprocal relationships.',
        };
    }
}
