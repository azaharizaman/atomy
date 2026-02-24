<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Enums;

/**
 * Ledger Type enum
 * 
 * Classifies ledgers by their purpose and accounting treatment.
 */
enum LedgerType: string
{
    case STATUTORY = 'statutory';
    case MANAGEMENT = 'management';

    /**
     * Check if this is a statutory ledger
     */
    public function isStatutory(): bool
    {
        return $this === self::STATUTORY;
    }

    /**
     * Check if this is a management ledger
     */
    public function isManagement(): bool
    {
        return $this === self::MANAGEMENT;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::STATUTORY => 'Statutory Ledger',
            self::MANAGEMENT => 'Management Ledger',
        };
    }
}
