<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Enums;

/**
 * Capacity Load Type enum.
 *
 * Defines how capacity is loaded onto a work center.
 */
enum CapacityLoadType: string
{
    /**
     * Finite loading - respects capacity limits.
     * Orders scheduled within available capacity.
     */
    case FINITE = 'finite';

    /**
     * Infinite loading - ignores capacity limits.
     * Orders scheduled based on dates only, may overload.
     */
    case INFINITE = 'infinite';

    /**
     * Bottleneck loading - finite for bottlenecks only.
     * Hybrid approach focusing constraints on critical resources.
     */
    case BOTTLENECK = 'bottleneck';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::FINITE => 'Finite Loading',
            self::INFINITE => 'Infinite Loading',
            self::BOTTLENECK => 'Bottleneck Loading',
        };
    }

    /**
     * Get description.
     */
    public function description(): string
    {
        return match ($this) {
            self::FINITE => 'Schedule orders within available capacity limits',
            self::INFINITE => 'Schedule orders based on dates, ignoring capacity limits',
            self::BOTTLENECK => 'Apply finite loading only to bottleneck work centers',
        };
    }

    /**
     * Check if this type respects capacity constraints.
     */
    public function respectsCapacity(): bool
    {
        return match ($this) {
            self::FINITE, self::BOTTLENECK => true,
            self::INFINITE => false,
        };
    }
}
