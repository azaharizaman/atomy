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
        // Zero balance should be marked as NONE
        if ($this->amount->isZero() && $this->balanceType !== BalanceType::NONE) {
            throw new \InvalidArgumentException(
                'Zero amount must have BalanceType::NONE'
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
            amount: $amount,
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
            amount: $amount,
            balanceType: BalanceType::CREDIT,
        );
    }

    /**
     * Get the amount as a signed value
     * 
     * Returns positive for debit balances on debit-balanced accounts,
     * and negative for credit balances on debit-balanced accounts.
     * For credit-balanced accounts, this is reversed.
     */
    public function getSignedAmount(BalanceType $accountType): Money
    {
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
     */
    public function add(AccountBalance $other): self
    {
        if ($this->amount->getCurrency() !== $other->amount->getCurrency()) {
            throw new \InvalidArgumentException(
                'Cannot add balances with different currencies'
            );
        }

        $newAmount = $this->amount->add($other->amount);
        
        // Determine new balance type
        $newType = match (true) {
            $newAmount->isZero() => BalanceType::NONE,
            $newAmount->isPositive() => $this->balanceType,
            default => $this->balanceType->isDebit() ? BalanceType::CREDIT : BalanceType::DEBIT,
        };

        return new self(
            amount: $newAmount->abs(),
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

        return $this->add(new self(
            amount: $other->amount->negate(),
            balanceType: $other->balanceType,
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
