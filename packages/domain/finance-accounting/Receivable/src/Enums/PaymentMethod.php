<?php

declare(strict_types=1);

namespace Nexus\Receivable\Enums;

/**
 * Payment Method Types
 *
 * Defines the method by which a customer payment was received.
 */
enum PaymentMethod: string
{
    case BANK_TRANSFER = 'bank_transfer';
    case CHEQUE = 'cheque';
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case CASH = 'cash';
    case ONLINE = 'online';
    case DIRECT_DEBIT = 'direct_debit';

    /**
     * Does this method require bank clearance time?
     */
    public function requiresClearance(): bool
    {
        return in_array($this, [self::CHEQUE, self::BANK_TRANSFER, self::DIRECT_DEBIT], true);
    }

    /**
     * Can this method bounce/fail?
     */
    public function canBounce(): bool
    {
        return in_array($this, [self::CHEQUE, self::DIRECT_DEBIT], true);
    }

    /**
     * Get typical clearance days
     */
    public function getClearanceDays(): int
    {
        return match ($this) {
            self::CHEQUE => 3,
            self::BANK_TRANSFER => 1,
            self::DIRECT_DEBIT => 2,
            default => 0,
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CHEQUE => 'Cheque',
            self::CREDIT_CARD => 'Credit Card',
            self::DEBIT_CARD => 'Debit Card',
            self::CASH => 'Cash',
            self::ONLINE => 'Online Payment',
            self::DIRECT_DEBIT => 'Direct Debit',
        };
    }
}
