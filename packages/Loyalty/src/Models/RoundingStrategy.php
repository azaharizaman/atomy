<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Models;

/**
 * Enumerated Rounding Strategies for point calculation.
 * Requirement: FUN-LOY-101
 */
enum RoundingStrategy: string
{
    case Floor = 'floor';
    case Ceil = 'ceil';
    case Nearest = 'nearest';

    /**
     * Apply the rounding strategy to a numeric value.
     */
    public function apply(float $value): int
    {
        return match ($this) {
            self::Floor => (int) floor($value),
            self::Ceil => (int) ceil($value),
            self::Nearest => (int) round($value),
        };
    }
}
