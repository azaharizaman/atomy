<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Enums;

/**
 * Balance Type enum
 * 
 * Represents the type of account balance based on accounting equation.
 */
enum BalanceType: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';
    case NONE = 'none';

    /**
     * Check if this is a debit balance
     */
    public function isDebit(): bool
    {
        return $this === self::DEBIT;
    }

    /**
     * Check if this is a credit balance
     */
    public function isCredit(): bool
    {
        return $this === self::CREDIT;
    }

    /**
     * Check if there is no balance
     */
    public function isNone(): bool
    {
        return $this === self::NONE;
    }

    /**
     * Get the opposite balance type
     */
    public function opposite(): self
    {
        return match ($this) {
            self::DEBIT => self::CREDIT,
            self::CREDIT => self::DEBIT,
            self::NONE => self::NONE,
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::DEBIT => 'Debit Balance',
            self::CREDIT => 'Credit Balance',
            self::NONE => 'Zero Balance',
        };
    }
}
