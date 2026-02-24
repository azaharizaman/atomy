<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Enums;

/**
 * Ledger Status enum
 * 
 * Represents the current state of a ledger.
 */
enum LedgerStatus: string
{
    case ACTIVE = 'active';
    case CLOSED = 'closed';
    case ARCHIVED = 'archived';

    /**
     * Check if ledger is active
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if ledger is closed
     */
    public function isClosed(): bool
    {
        return $this === self::CLOSED;
    }

    /**
     * Check if ledger is archived
     */
    public function isArchived(): bool
    {
        return $this === self::ARCHIVED;
    }

    /**
     * Check if transactions can be posted
     */
    public function canPostTransactions(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::CLOSED => 'Closed',
            self::ARCHIVED => 'Archived',
        };
    }
}
