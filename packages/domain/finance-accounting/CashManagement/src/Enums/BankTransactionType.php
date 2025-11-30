<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Enums;

/**
 * Bank Transaction Type Enumeration
 *
 * Defines the types of transactions found on bank statements.
 */
enum BankTransactionType: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case TRANSFER = 'transfer';
    case FEE = 'fee';
    case INTEREST = 'interest';
    case CHECK = 'check';
    case ATM = 'atm';
    case DIRECT_DEBIT = 'direct_debit';
    case DIRECT_CREDIT = 'direct_credit';
    case REVERSAL = 'reversal';
    case OTHER = 'other';

    /**
     * Determine if transaction increases balance
     */
    public function isInflow(): bool
    {
        return match ($this) {
            self::DEPOSIT, self::INTEREST, self::DIRECT_CREDIT => true,
            default => false,
        };
    }

    /**
     * Determine if transaction decreases balance
     */
    public function isOutflow(): bool
    {
        return match ($this) {
            self::WITHDRAWAL, self::FEE, self::CHECK, self::ATM, self::DIRECT_DEBIT => true,
            default => false,
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT => 'Deposit',
            self::WITHDRAWAL => 'Withdrawal',
            self::TRANSFER => 'Transfer',
            self::FEE => 'Bank Fee',
            self::INTEREST => 'Interest',
            self::CHECK => 'Check',
            self::ATM => 'ATM Transaction',
            self::DIRECT_DEBIT => 'Direct Debit',
            self::DIRECT_CREDIT => 'Direct Credit',
            self::REVERSAL => 'Reversal',
            self::OTHER => 'Other',
        };
    }
}
