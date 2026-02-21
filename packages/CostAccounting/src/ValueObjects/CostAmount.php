<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\ValueObjects;

/**
 * Cost Amount Value Object
 * 
 * Immutable value object representing a monetary cost amount.
 * Uses integer cents internally to avoid floating-point precision errors.
 */
final readonly class CostAmount
{
    private const CENTS_PRECISION = 2;

    public function __construct(
        private int $cents,
        private string $currency = 'USD'
    ) {
        if ($cents < 0) {
            throw new \InvalidArgumentException(
                'Cost amount cannot be negative'
            );
        }

        $normalizedCurrency = strtoupper($currency);
        if (!preg_match('/^[A-Z]{3}$/', $normalizedCurrency)) {
            throw new \InvalidArgumentException(
                sprintf('Currency must be a 3-character uppercase ISO 4217 code, got "%s"', $currency)
            );
        }
        $this->currency = $normalizedCurrency;
    }

    public static function fromFloat(float $amount, string $currency = 'USD'): self
    {
        $cents = (int) round($amount * 100);
        return new self($cents, $currency);
    }

    public static function fromCents(int $cents, string $currency = 'USD'): self
    {
        return new self($cents, $currency);
    }

    public function getCents(): int
    {
        return $this->cents;
    }

    public function getAmount(): float
    {
        return round($this->cents / 100, self::CENTS_PRECISION);
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(CostAmount $other): CostAmount
    {
        $this->validateCurrency($other);
        
        return new CostAmount(
            $this->cents + $other->cents,
            $this->currency
        );
    }

    public function subtract(CostAmount $other): CostAmount
    {
        $this->validateCurrency($other);
        
        $resultCents = $this->cents - $other->cents;
        
        if ($resultCents < 0) {
            throw new \DomainException(
                'Subtraction result would be negative'
            );
        }
        
        return new CostAmount(
            $resultCents,
            $this->currency
        );
    }

    public function multiply(float $factor): CostAmount
    {
        if ($factor < 0) {
            throw new \InvalidArgumentException(
                'Factor must be non-negative'
            );
        }
        
        $resultCents = (int) round($this->cents * $factor);
        
        return new CostAmount(
            $resultCents,
            $this->currency
        );
    }

    public function divide(float $divisor): CostAmount
    {
        if ($divisor <= 0) {
            throw new \InvalidArgumentException(
                'Divisor must be positive'
            );
        }
        
        $resultCents = (int) round($this->cents / $divisor);
        
        return new CostAmount(
            $resultCents,
            $this->currency
        );
    }

    public function isGreaterThan(CostAmount $other): bool
    {
        $this->validateCurrency($other);
        
        return $this->cents > $other->cents;
    }

    public function isLessThan(CostAmount $other): bool
    {
        $this->validateCurrency($other);
        
        return $this->cents < $other->cents;
    }

    public function isEqualTo(CostAmount $other): bool
    {
        $this->validateCurrency($other);
        
        return $this->cents === $other->cents;
    }

    public function __toString(): string
    {
        $majorUnits = round($this->cents / 100, self::CENTS_PRECISION);
        
        return sprintf('%s %.2f', $this->currency, $majorUnits);
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
