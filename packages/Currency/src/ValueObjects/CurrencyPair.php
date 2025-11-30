<?php

declare(strict_types=1);

namespace Nexus\Currency\ValueObjects;

use InvalidArgumentException;

/**
 * Currency Pair Value Object
 *
 * Immutable representation of a currency exchange pair (e.g., USD/EUR).
 *
 * @package Nexus\Currency\ValueObjects
 */
final readonly class CurrencyPair
{
    /**
     * @param string $fromCode Source currency code (e.g., "USD")
     * @param string $toCode Target currency code (e.g., "EUR")
     */
    public function __construct(
        public string $fromCode,
        public string $toCode
    ) {
        $this->validateCurrencyCode($fromCode, 'from');
        $this->validateCurrencyCode($toCode, 'to');

        if ($fromCode === $toCode) {
            throw new InvalidArgumentException(
                "Currency pair must have different currencies, got: {$fromCode}/{$toCode}"
            );
        }
    }

    /**
     * Create a currency pair from string notation (e.g., "USD/EUR")
     */
    public static function fromString(string $pair): self
    {
        $parts = explode('/', $pair);

        if (count($parts) !== 2) {
            throw new InvalidArgumentException(
                "Currency pair must be in format 'FROM/TO', got: {$pair}"
            );
        }

        return new self(trim($parts[0]), trim($parts[1]));
    }

    /**
     * Get the source currency code
     */
    public function getFromCode(): string
    {
        return $this->fromCode;
    }

    /**
     * Get the target currency code
     */
    public function getToCode(): string
    {
        return $this->toCode;
    }

    /**
     * Get the inverse pair (e.g., EUR/USD for USD/EUR)
     */
    public function inverse(): self
    {
        return new self($this->toCode, $this->fromCode);
    }

    /**
     * Check if this pair matches another
     */
    public function equals(self $other): bool
    {
        return $this->fromCode === $other->fromCode
            && $this->toCode === $other->toCode;
    }

    /**
     * Format as string (e.g., "USD/EUR")
     */
    public function toString(): string
    {
        return "{$this->fromCode}/{$this->toCode}";
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    private function validateCurrencyCode(string $code, string $position): void
    {
        if (strlen($code) !== 3) {
            throw new InvalidArgumentException(
                "Currency code ({$position}) must be 3 characters, got: {$code}"
            );
        }

        if (!ctype_upper($code)) {
            throw new InvalidArgumentException(
                "Currency code ({$position}) must be uppercase: {$code}"
            );
        }

        if (!ctype_alpha($code)) {
            throw new InvalidArgumentException(
                "Currency code ({$position}) must contain only letters: {$code}"
            );
        }
    }
}
