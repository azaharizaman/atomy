<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\ValueObjects;

/**
 * Cost Amount Value Object
 * 
 * Immutable value object representing a monetary cost amount.
 */
final readonly class CostAmount
{
    public function __construct(
        private float $amount,
        private string $currency = 'USD'
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException(
                'Cost amount cannot be negative'
            );
        }
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(CostAmount $other): CostAmount
    {
        $this->validateCurrency($other);
        
        return new CostAmount(
            $this->amount + $other->amount,
            $this->currency
        );
    }

    public function subtract(CostAmount $other): CostAmount
    {
        $this->validateCurrency($other);
        
        return new CostAmount(
            $this->amount - $other->amount,
            $this->currency
        );
    }

    public function multiply(float $factor): CostAmount
    {
        return new CostAmount(
            $this->amount * $factor,
            $this->currency
        );
    }

    public function divide(float $divisor): CostAmount
    {
        if ($divisor === 0.0) {
            throw new \InvalidArgumentException(
                'Cannot divide by zero'
            );
        }
        
        return new CostAmount(
            $this->amount / $divisor,
            $this->currency
        );
    }

    public function isGreaterThan(CostAmount $other): bool
    {
        $this->validateCurrency($other);
        
        return $this->amount > $other->amount;
    }

    public function isLessThan(CostAmount $other): bool
    {
        $this->validateCurrency($other);
        
        return $this->amount < $other->amount;
    }

    public function isEqualTo(CostAmount $other): bool
    {
        $this->validateCurrency($other);
        
        return $this->amount === $other->amount;
    }

    public function __toString(): string
    {
        return sprintf('%s %.2f', $this->currency, $this->amount);
    }

    private function validateCurrency(CostAmount $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Currency mismatch: %s and %s',
                    $this->currency,
                    $other->currency
                )
            );
        }
    }
}
