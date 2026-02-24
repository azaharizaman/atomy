<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\GeneralLedger\Enums\BalanceType;

/**
 * Account Balance Value Object
 * 
 * Represents the balance of an account at a point in time, including
 * both the monetary amount and the balance type (debit or credit).
 * 
 * This value object encapsulates the complexity of accounting balance
 * calculations. The balance type indicates whether increases to the 
 * account are recorded as debits or credits:
 * - DEBIT: Assets and Expenses (debits increase, credits decrease)
 * - CREDIT: Liabilities, Equity, and Revenue (credits increase, debits decrease)
 */
final readonly class AccountBalance
{
    /**
     * @param Money $amount The monetary amount (always positive, type indicates sign)
     * @param BalanceType $balanceType Whether this is a debit or credit balance
     */
    public function __construct(
        public Money $amount,
        public BalanceType $balanceType,
    ) {
        // Amount must be positive
        if (!$this->amount->isPositive() && !$this->amount->isZero()) {
             throw new \InvalidArgumentException(
                'Amount must be positive or zero'
            );
        }

        // Zero balance should be marked as NONE
        if ($this->amount->isZero() && $this->balanceType !== BalanceType::NONE) {
            throw new \InvalidArgumentException(
                'Zero amount must have BalanceType::NONE'
            );
        }

        // Positive balance should NOT be marked as NONE
        if ($this->amount->isPositive() && $this->balanceType === BalanceType::NONE) {
            throw new \InvalidArgumentException(
                'Positive amount must have DEBIT or CREDIT balance type'
            );
        }
    }

    /**
     * Create a zero balance
     */
    public static function zero(string $currency = 'USD'): self
    {
        return new self(
            amount: Money::zero($currency),
            balanceType: BalanceType::NONE,
        );
    }

    /**
     * Create a debit balance
     */
    public static function debit(Money $amount): self
    {
        if ($amount->isZero()) {
            return self::zero($amount->getCurrency());
        }

        return new self(
            amount: $amount->abs(),
            balanceType: BalanceType::DEBIT,
        );
    }

    /**
     * Create a credit balance
     */
    public static function credit(Money $amount): self
    {
        if ($amount->isZero()) {
            return self::zero($amount->getCurrency());
        }

        return new self(
            amount: $amount->abs(),
            balanceType: BalanceType::CREDIT,
        );
    }

    /**
     * Get the amount as a signed value
     * 
     * Returns positive for debit balances on debit-balanced accounts,
     * and negative for credit balances on debit-balanced accounts.
     * For credit-balanced accounts, this is reversed.
     * 
     * @param BalanceType $accountType The natural balance type of the account (DEBIT or CREDIT)
     * @throws \InvalidArgumentException If BalanceType::NONE is passed as accountType
     */
    public function getSignedAmount(BalanceType $accountType): Money
    {
        if ($accountType === BalanceType::NONE) {
            throw new \InvalidArgumentException(
                'BalanceType::NONE is not a valid account type for getSignedAmount'
            );
        }

        if ($this->amount->isZero()) {
            return $this->amount;
        }

        // If account is debit-balanced (assets/expenses)
        if ($accountType === BalanceType::DEBIT) {
            return $this->balanceType === BalanceType::DEBIT 
                ? $this->amount 
                : $this->amount->negate();
        }

        // If account is credit-balanced (liabilities/equity/revenue)
        return $this->balanceType === BalanceType::CREDIT 
            ? $this->amount 
            : $this->amount->negate();
    }

    /**
     * Check if this is a debit balance
     */
    public function isDebit(): bool
    {
        return $this->balanceType === BalanceType::DEBIT;
    }

    /**
     * Check if this is a credit balance
     */
    public function isCredit(): bool
    {
        return $this->balanceType === BalanceType::CREDIT;
    }

    /**
     * Check if there is no balance
     */
    public function isZero(): bool
    {
        return $this->amount->isZero();
    }

    /**
     * Add another balance to this one
     * 
     * When adding balances with different types (debit + credit),
     * the amounts are netted against each other.
     */
    public function add(AccountBalance $other): self
    {
        if ($this->amount->getCurrency() !== $other->amount->getCurrency()) {
            throw new \InvalidArgumentException(
                'Cannot add balances with different currencies'
            );
        }

        if ($other->isZero()) {
            return $this;
        }

        if ($this->isZero()) {
            return $other;
        }

        // Determine base type for sign calculation
        // If current is NONE, we use the other's type
        $baseType = ($this->balanceType === BalanceType::NONE) 
            ? $other->balanceType 
            : $this->balanceType;

        // Get signed amounts for proper netting
        $thisSigned = $this->amount;
        $otherSigned = $other->amount;
        
        // Negate the other amount if it has opposite balance type relative to baseType
        if ($baseType !== $other->balanceType) {
            $otherSigned = $otherSigned->negate();
        }
        
        $newAmountValue = $thisSigned->add($otherSigned);
        
        // Determine new balance type based on the sign of the result relative to baseType
        $newType = match (true) {
            $newAmountValue->isZero() => BalanceType::NONE,
            $newAmountValue->isPositive() => $baseType,
            default => $baseType->opposite(),
        };

        return new self(
            amount: $newAmountValue->abs(),
            balanceType: $newType,
        );
    }

    /**
     * Subtract another balance from this one
     */
    public function subtract(AccountBalance $other): self
    {
        if ($this->amount->getCurrency() !== $other->amount->getCurrency()) {
            throw new \InvalidArgumentException(
                'Cannot subtract balances with different currencies'
            );
        }

        if ($other->isZero()) {
            return $this;
        }

        // Subtraction is equivalent to adding the opposite balance type
        return $this->add(new self(
            amount: $other->amount,
            balanceType: $other->balanceType->opposite(),
        ));
    }

    /**
     * Get the currency code
     */
    public function getCurrency(): string
    {
        return $this->amount->getCurrency();
    }

    /**
     * Get the amount
     */
    public function getAmount(): Money
    {
        return $this->amount;
    }

    /**
     * Get the amount in minor units
     */
    public function getAmountInMinorUnits(): int
    {
        return $this->amount->getAmountInMinorUnits();
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount->getAmount(),
            'currency' => $this->amount->getCurrency(),
            'balance_type' => $this->balanceType->value,
        ];
    }
}
