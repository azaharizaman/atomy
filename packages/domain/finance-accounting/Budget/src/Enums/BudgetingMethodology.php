<?php

declare(strict_types=1);

namespace Nexus\Budget\Enums;

/**
 * Budgeting Methodology enum
 * 
 * Defines the budgeting approach used.
 */
enum BudgetingMethodology: string
{
    case Incremental = 'incremental';
    case ZeroBased = 'zero_based';

    /**
     * Check if methodology requires justification
     */
    public function requiresJustification(): bool
    {
        return $this === self::ZeroBased;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::Incremental => 'Incremental Budgeting',
            self::ZeroBased => 'Zero-Based Budgeting (ZBB)',
        };
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return match($this) {
            self::Incremental => 'Budget based on prior period with adjustments',
            self::ZeroBased => 'Budget built from zero, requiring justification for every expense',
        };
    }
}
