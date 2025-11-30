<?php

declare(strict_types=1);

namespace Nexus\SharedKernel\ValueObjects;

use Nexus\SharedKernel\Exceptions\InvalidValueException;

/**
 * Money Value Object
 *
 * Immutable representation of monetary amount with currency.
 * Uses 4 decimal precision for financial calculations.
 *
 * All monetary operations use BCMath for precision.
 */
final readonly class Money
{
    private const PRECISION = 4;

    /**
     * @param string $amount Amount as string to avoid floating point issues
     * @param string $currency ISO 4217 currency code (e.g., "MYR", "USD")
     */
    public function __construct(
        private string $amount,
        private string $currency
    ) {
        $this->validateAmount($amount);
        $this->validateCurrency($currency);
    }

    /**
     * Create Money from numeric value
     *
     * Uses BCMath for precision-safe string formatting.
     */
    public static function of(float|int|string $amount, string $currency): self
    {
        // Convert to string first to avoid float precision issues
        $stringAmount = is_string($amount) ? $amount : (string)$amount;

        // Use bcadd with zero to normalize and format with proper precision
        $formatted = bcadd($stringAmount, '0', self::PRECISION);

        return new self($formatted, strtoupper($currency));
    }

    /**
     * Create zero money in a currency
     */
    public static function zero(string $currency): self
    {
        return new self('0.0000', strtoupper($currency));
    }

    /**
     * Get the amount as a string with 4 decimal places
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Get the amount as a float (use with caution)
     */
    public function toFloat(): float
    {
        return (float)$this->amount;
    }

    /**
     * Get the currency code
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Add another Money object
     *
     * @throws InvalidValueException if currencies don't match
     */
    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        $sum = bcadd($this->amount, $other->amount, self::PRECISION);
        return new self($sum, $this->currency);
    }

    /**
     * Subtract another Money object
     *
     * @throws InvalidValueException if currencies don't match
     */
    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        $diff = bcsub($this->amount, $other->amount, self::PRECISION);
        return new self($diff, $this->currency);
    }

    /**
     * Multiply by a factor
     */
    public function multiply(float|int|string $factor): self
    {
        $product = bcmul($this->amount, (string)$factor, self::PRECISION);
        return new self($product, $this->currency);
    }

    /**
     * Divide by a divisor
     *
     * @throws InvalidValueException if divisor is zero
     */
    public function divide(float|int|string $divisor): self
    {
        if (bccomp((string)$divisor, '0', self::PRECISION) === 0) {
            throw new InvalidValueException('Cannot divide by zero');
        }

        $quotient = bcdiv($this->amount, (string)$divisor, self::PRECISION);
        return new self($quotient, $this->currency);
    }

    /**
     * Check if this money is equal to another
     */
    public function equals(self $other): bool
    {
        return $this->currency === $other->currency
            && bccomp($this->amount, $other->amount, self::PRECISION) === 0;
    }

    /**
     * Check if this money is greater than another
     */
    public function greaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);
        return bccomp($this->amount, $other->amount, self::PRECISION) > 0;
    }

    /**
     * Check if this money is less than another
     */
    public function lessThan(self $other): bool
    {
        $this->assertSameCurrency($other);
        return bccomp($this->amount, $other->amount, self::PRECISION) < 0;
    }

    /**
     * Check if this is zero
     */
    public function isZero(): bool
    {
        return bccomp($this->amount, '0', self::PRECISION) === 0;
    }

    /**
     * Check if this is positive
     */
    public function isPositive(): bool
    {
        return bccomp($this->amount, '0', self::PRECISION) > 0;
    }

    /**
     * Check if this is negative
     */
    public function isNegative(): bool
    {
        return bccomp($this->amount, '0', self::PRECISION) < 0;
    }

    /**
     * Get absolute value
     */
    public function abs(): self
    {
        if ($this->isNegative()) {
            return new self(bcmul($this->amount, '-1', self::PRECISION), $this->currency);
        }
        return $this;
    }

    /**
     * Negate the amount
     */
    public function negate(): self
    {
        if ($this->isZero()) {
            return $this;
        }

        $negated = bcmul($this->amount, '-1', self::PRECISION);
        return new self($negated, $this->currency);
    }

    /**
     * Format for display (e.g., "1,000.00 MYR")
     */
    public function format(int $decimals = 2): string
    {
        return number_format($this->toFloat(), $decimals, '.', ',') . ' ' . $this->currency;
    }

    public function __toString(): string
    {
        return $this->amount . ' ' . $this->currency;
    }

    private function validateAmount(string $amount): void
    {
        if (!is_numeric($amount)) {
            throw new InvalidValueException("Money amount must be numeric, got: {$amount}");
        }
    }

    private function validateCurrency(string $currency): void
    {
        if (strlen($currency) !== 3) {
            throw new InvalidValueException("Currency must be 3-letter ISO code, got: {$currency}");
        }

        if (!ctype_upper($currency)) {
            throw new InvalidValueException("Currency code must be uppercase: {$currency}");
        }
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidValueException(
                "Cannot operate on different currencies: {$this->currency} and {$other->currency}"
            );
        }
    }
}
