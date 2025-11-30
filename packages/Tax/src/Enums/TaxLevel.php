<?php

declare(strict_types=1);

namespace Nexus\Tax\Enums;

/**
 * Tax Level: Jurisdiction hierarchy level
 * 
 * Represents the governmental level that levies the tax.
 * Used for hierarchical tax calculations and reporting.
 */
enum TaxLevel: string
{
    case Federal = 'federal';
    case State = 'state';
    case Local = 'local';
    case Municipal = 'municipal';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Federal => 'Federal/National',
            self::State => 'State/Provincial',
            self::Local => 'County/Local',
            self::Municipal => 'City/Municipal',
        };
    }

    /**
     * Get hierarchy order (0 = highest/broadest, 3 = lowest/most specific)
     */
    public function getHierarchyOrder(): int
    {
        return match ($this) {
            self::Federal => 0,
            self::State => 1,
            self::Local => 2,
            self::Municipal => 3,
        };
    }

    /**
     * Check if this level is higher than another in hierarchy
     */
    public function isHigherThan(self $other): bool
    {
        return $this->getHierarchyOrder() < $other->getHierarchyOrder();
    }

    /**
     * Check if this level is lower than another in hierarchy
     */
    public function isLowerThan(self $other): bool
    {
        return $this->getHierarchyOrder() > $other->getHierarchyOrder();
    }

    /**
     * Get all levels from this level upward in hierarchy
     * 
     * @return array<self>
     */
    public function getParentLevels(): array
    {
        $parentLevels = [];
        
        foreach (self::cases() as $level) {
            if ($level->isHigherThan($this)) {
                $parentLevels[] = $level;
            }
        }

        return $parentLevels;
    }
}
