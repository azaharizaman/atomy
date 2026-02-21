<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Enums;

/**
 * Cost Element Type enum
 * 
 * Categorizes costs by type for tracking and allocation.
 */
enum CostElementType: string
{
    case Material = 'material';
    case Labor = 'labor';
    case Overhead = 'overhead';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::Material => 'Material',
            self::Labor => 'Labor',
            self::Overhead => 'Overhead',
        };
    }

    /**
     * Check if this is a direct cost
     */
    public function isDirectCost(): bool
    {
        return match($this) {
            self::Material, self::Labor => true,
            self::Overhead => false,
        };
    }

    /**
     * Check if this is an indirect cost
     */
    public function isIndirectCost(): bool
    {
        return !$this->isDirectCost();
    }
}
