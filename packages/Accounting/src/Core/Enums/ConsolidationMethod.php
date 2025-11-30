<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\Enums;

/**
 * Consolidation methods.
 */
enum ConsolidationMethod: string
{
    case FULL = 'full';
    case PROPORTIONAL = 'proportional';
    case EQUITY = 'equity';

    /**
     * Get the display name.
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::FULL => 'Full Consolidation',
            self::PROPORTIONAL => 'Proportional Consolidation',
            self::EQUITY => 'Equity Method',
        };
    }

    /**
     * Get the description.
     */
    public function getDescription(): string
    {
        return match($this) {
            self::FULL => 'Consolidate 100% of assets, liabilities, and equity with NCI calculation',
            self::PROPORTIONAL => 'Consolidate only the proportional share of assets and liabilities',
            self::EQUITY => 'Report investment as a single line item on balance sheet',
        };
    }

    /**
     * Get typical ownership threshold.
     */
    public function getOwnershipThreshold(): float
    {
        return match($this) {
            self::FULL => 50.0,
            self::PROPORTIONAL => 20.0,
            self::EQUITY => 20.0,
        };
    }

    /**
     * Check if this method requires elimination entries.
     */
    public function requiresEliminations(): bool
    {
        return match($this) {
            self::FULL, self::PROPORTIONAL => true,
            self::EQUITY => false,
        };
    }
}
