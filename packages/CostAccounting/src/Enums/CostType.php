<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Enums;

/**
 * Cost Type Enum
 * 
 * Represents the type of cost calculation.
 */
enum CostType: string
{
    case Standard = 'standard';
    case Actual = 'actual';

    public function label(): string
    {
        return match($this) {
            self::Standard => 'Standard',
            self::Actual => 'Actual',
        };
    }
}
