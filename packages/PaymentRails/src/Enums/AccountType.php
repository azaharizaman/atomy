<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Enums;

/**
 * Account type for bank accounts.
 */
enum AccountType: string
{
    /**
     * Checking/Current account.
     */
    case CHECKING = 'checking';

    /**
     * Savings account.
     */
    case SAVINGS = 'savings';

    /**
     * General Ledger account (internal).
     */
    case GL = 'gl';

    /**
     * Loan account.
     */
    case LOAN = 'loan';

    /**
     * Get the corresponding ACH transaction code for credits.
     */
    public function achCreditCode(): ?TransactionCode
    {
        return match ($this) {
            self::CHECKING => TransactionCode::CHECKING_CREDIT,
            self::SAVINGS => TransactionCode::SAVINGS_CREDIT,
            default => null,
        };
    }

    /**
     * Get the corresponding ACH transaction code for debits.
     */
    public function achDebitCode(): ?TransactionCode
    {
        return match ($this) {
            self::CHECKING => TransactionCode::CHECKING_DEBIT,
            self::SAVINGS => TransactionCode::SAVINGS_DEBIT,
            default => null,
        };
    }

    /**
     * Check if this account type supports ACH transactions.
     */
    public function supportsAch(): bool
    {
        return match ($this) {
            self::CHECKING, self::SAVINGS => true,
            default => false,
        };
    }

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::CHECKING => 'Checking',
            self::SAVINGS => 'Savings',
            self::GL => 'General Ledger',
            self::LOAN => 'Loan',
        };
    }
}
