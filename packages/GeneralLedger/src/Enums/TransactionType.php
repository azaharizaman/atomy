<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Enums;

/**
 * Transaction Type enum
 * 
 * Represents the type of financial transaction (debit or credit).
 */
enum TransactionType: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';

    /**
     * Check if this is a debit transaction
     */
    public function isDebit(): bool
    {
        return $this === self::DEBIT;
    }

    /**
     * Check if this is a credit transaction
     */
    public function isCredit(): bool
    {
        return $this === self::CREDIT;
    }

    /**
     * Get the opposite transaction type
     */
    public function opposite(): self
    {
        return match ($this) {
            self::DEBIT => self::CREDIT,
            self::CREDIT => self::DEBIT,
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::DEBIT => 'Debit',
            self::CREDIT => 'Credit',
        };
    }
}
