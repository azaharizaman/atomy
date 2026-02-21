<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\ValueObjects;

/**
 * Immutable value object representing a depreciation amount.
 */
final readonly class DepreciationAmount
{
    public function __construct(
        public float $amount,
        public string $currency,
        public ?float $accumulatedDepreciation = null,
    ) {}

    /**
     * Add another depreciation amount to this one.
     */
    public function add(self $other): self
    {
        if ($other->currency !== $this->currency) {
            throw new \InvalidArgumentException('Cannot add depreciation amounts with different currencies');
        }

        return new self(
            amount: $this->amount + $other->amount,
            currency: $this->currency,
            accumulatedDepreciation: ($this->accumulatedDepreciation ?? 0) + ($other->accumulatedDepreciation ?? 0) + $other->amount,
        );
    }

    /**
     * Subtract another depreciation amount from this one.
     */
    public function subtract(self $other): self
    {
        if ($other->currency !== $this->currency) {
            throw new \InvalidArgumentException('Cannot subtract depreciation amounts with different currencies');
        }

        return new self(
            amount: $this->amount - $other->amount,
            currency: $this->currency,
            accumulatedDepreciation: ($this->accumulatedDepreciation ?? 0) - ($other->accumulatedDepreciation ?? 0),
        );
    }

    /**
     * Multiply this amount by a factor.
     */
    public function multiply(float $factor): self
    {
        return new self(
            amount: $this->amount * $factor,
            currency: $this->currency,
            accumulatedDepreciation: $this->accumulatedDepreciation !== null 
                ? $this->accumulatedDepreciation * $factor 
                : null,
        );
    }

    /**
     * Get the amount value.
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Format as string with currency.
     */
    public function format(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }
}
