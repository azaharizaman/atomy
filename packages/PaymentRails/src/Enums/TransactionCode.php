<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Enums;

/**
 * ACH Transaction Codes as defined by NACHA.
 *
 * Transaction codes identify the account type and transaction direction
 * for ACH entries. The first digit indicates the account type:
 * - 2x = Checking
 * - 3x = Savings
 *
 * The second digit indicates the transaction type:
 * - x2 = Credit (deposit)
 * - x3 = Credit Prenote
 * - x7 = Debit (withdrawal)
 * - x8 = Debit Prenote
 */
enum TransactionCode: string
{
    /**
     * Automated Deposit to Checking Account (Credit).
     */
    case CHECKING_CREDIT = '22';

    /**
     * Prenote for Checking Credit.
     * Zero-dollar test transaction.
     */
    case CHECKING_CREDIT_PRENOTE = '23';

    /**
     * Zero Dollar Remittance Checking Credit.
     */
    case CHECKING_CREDIT_ZERO = '24';

    /**
     * Automated Payment from Checking Account (Debit).
     */
    case CHECKING_DEBIT = '27';

    /**
     * Prenote for Checking Debit.
     * Zero-dollar test transaction.
     */
    case CHECKING_DEBIT_PRENOTE = '28';

    /**
     * Zero Dollar Remittance Checking Debit.
     */
    case CHECKING_DEBIT_ZERO = '29';

    /**
     * Automated Deposit to Savings Account (Credit).
     */
    case SAVINGS_CREDIT = '32';

    /**
     * Prenote for Savings Credit.
     * Zero-dollar test transaction.
     */
    case SAVINGS_CREDIT_PRENOTE = '33';

    /**
     * Zero Dollar Remittance Savings Credit.
     */
    case SAVINGS_CREDIT_ZERO = '34';

    /**
     * Automated Payment from Savings Account (Debit).
     */
    case SAVINGS_DEBIT = '37';

    /**
     * Prenote for Savings Debit.
     * Zero-dollar test transaction.
     */
    case SAVINGS_DEBIT_PRENOTE = '38';

    /**
     * Zero Dollar Remittance Savings Debit.
     */
    case SAVINGS_DEBIT_ZERO = '39';

    /**
     * Get the numeric code value.
     */
    public function code(): int
    {
        return (int) $this->value;
    }

    /**
     * Check if this is a credit (deposit) transaction.
     */
    public function isCredit(): bool
    {
        return match ($this) {
            self::CHECKING_CREDIT,
            self::CHECKING_CREDIT_PRENOTE,
            self::CHECKING_CREDIT_ZERO,
            self::SAVINGS_CREDIT,
            self::SAVINGS_CREDIT_PRENOTE,
            self::SAVINGS_CREDIT_ZERO => true,
            default => false,
        };
    }

    /**
     * Check if this is a debit (withdrawal) transaction.
     */
    public function isDebit(): bool
    {
        return match ($this) {
            self::CHECKING_DEBIT,
            self::CHECKING_DEBIT_PRENOTE,
            self::CHECKING_DEBIT_ZERO,
            self::SAVINGS_DEBIT,
            self::SAVINGS_DEBIT_PRENOTE,
            self::SAVINGS_DEBIT_ZERO => true,
            default => false,
        };
    }

    /**
     * Check if this is a checking account transaction.
     */
    public function isChecking(): bool
    {
        return match ($this) {
            self::CHECKING_CREDIT,
            self::CHECKING_CREDIT_PRENOTE,
            self::CHECKING_CREDIT_ZERO,
            self::CHECKING_DEBIT,
            self::CHECKING_DEBIT_PRENOTE,
            self::CHECKING_DEBIT_ZERO => true,
            default => false,
        };
    }

    /**
     * Check if this is a savings account transaction.
     */
    public function isSavings(): bool
    {
        return match ($this) {
            self::SAVINGS_CREDIT,
            self::SAVINGS_CREDIT_PRENOTE,
            self::SAVINGS_CREDIT_ZERO,
            self::SAVINGS_DEBIT,
            self::SAVINGS_DEBIT_PRENOTE,
            self::SAVINGS_DEBIT_ZERO => true,
            default => false,
        };
    }

    /**
     * Check if this is a prenote (test) transaction.
     */
    public function isPrenote(): bool
    {
        return match ($this) {
            self::CHECKING_CREDIT_PRENOTE,
            self::CHECKING_DEBIT_PRENOTE,
            self::SAVINGS_CREDIT_PRENOTE,
            self::SAVINGS_DEBIT_PRENOTE => true,
            default => false,
        };
    }

    /**
     * Check if this is a zero-dollar remittance.
     */
    public function isZeroDollar(): bool
    {
        return match ($this) {
            self::CHECKING_CREDIT_ZERO,
            self::CHECKING_DEBIT_ZERO,
            self::SAVINGS_CREDIT_ZERO,
            self::SAVINGS_DEBIT_ZERO => true,
            default => false,
        };
    }

    /**
     * Get the corresponding prenote code.
     */
    public function toPrenote(): self
    {
        return match ($this) {
            self::CHECKING_CREDIT,
            self::CHECKING_CREDIT_PRENOTE,
            self::CHECKING_CREDIT_ZERO => self::CHECKING_CREDIT_PRENOTE,
            self::CHECKING_DEBIT,
            self::CHECKING_DEBIT_PRENOTE,
            self::CHECKING_DEBIT_ZERO => self::CHECKING_DEBIT_PRENOTE,
            self::SAVINGS_CREDIT,
            self::SAVINGS_CREDIT_PRENOTE,
            self::SAVINGS_CREDIT_ZERO => self::SAVINGS_CREDIT_PRENOTE,
            self::SAVINGS_DEBIT,
            self::SAVINGS_DEBIT_PRENOTE,
            self::SAVINGS_DEBIT_ZERO => self::SAVINGS_DEBIT_PRENOTE,
        };
    }

    /**
     * Create transaction code for credit to checking.
     */
    public static function creditToChecking(): self
    {
        return self::CHECKING_CREDIT;
    }

    /**
     * Create transaction code for debit from checking.
     */
    public static function debitFromChecking(): self
    {
        return self::CHECKING_DEBIT;
    }

    /**
     * Create transaction code for credit to savings.
     */
    public static function creditToSavings(): self
    {
        return self::SAVINGS_CREDIT;
    }

    /**
     * Create transaction code for debit from savings.
     */
    public static function debitFromSavings(): self
    {
        return self::SAVINGS_DEBIT;
    }
}
