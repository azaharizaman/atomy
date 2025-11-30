<?php

declare(strict_types=1);

namespace Nexus\Finance\Domain\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Exchange Rate Value Object
 * 
 * Immutable representation of a currency exchange rate.
 */
final readonly class ExchangeRate
{
    private const PRECISION = 6;

    public function __construct(
        private string $fromCurrency,
        private string $toCurrency,
        private string $rate,
        private DateTimeImmutable $effectiveDate
    ) {
        $this->validateCurrency($fromCurrency);
        $this->validateCurrency($toCurrency);
        $this->validateRate($rate);

        if ($fromCurrency === $toCurrency) {
            throw new InvalidArgumentException('From and To currencies must be different');
        }
    }

    public static function create(string $fromCurrency, string $toCurrency, float|string $rate, DateTimeImmutable $effectiveDate): self
    {
        $rateString = is_float($rate) ? number_format($rate, self::PRECISION, '.', '') : $rate;
        return new self($fromCurrency, $toCurrency, $rateString, $effectiveDate);
    }

    /**
     * Create a 1:1 rate for same currency (identity conversion)
     * 
     * Note: This creates a conceptual identity rate by appending '_BASE' suffix
     * to the target currency. This is a workaround since the constructor
     * enforces different currencies. For actual conversions, use the regular
     * constructor with different currency codes.
     * 
     * @deprecated Consider using Money operations directly when no conversion is needed
     */
    public static function identity(string $currency, DateTimeImmutable $effectiveDate): self
    {
        // For same currency, we create a conceptual identity by using a special suffix
        return new self($currency, $currency . '_BASE', '1.000000', $effectiveDate);
    }

    public function getFromCurrency(): string
    {
        return $this->fromCurrency;
    }

    public function getToCurrency(): string
    {
        return $this->toCurrency;
    }

    public function getRate(): string
    {
        return $this->rate;
    }

    public function getRateAsFloat(): float
    {
        return (float) $this->rate;
    }

    public function getEffectiveDate(): DateTimeImmutable
    {
        return $this->effectiveDate;
    }

    /**
     * Convert an amount using this exchange rate
     */
    public function convert(Money $amount): Money
    {
        if ($amount->getCurrency() !== $this->fromCurrency) {
            throw new InvalidArgumentException(
                "Amount currency {$amount->getCurrency()} does not match exchange rate from currency {$this->fromCurrency}"
            );
        }

        $convertedAmount = bcmul($amount->getAmount(), $this->rate, 4);
        return Money::of($convertedAmount, $this->toCurrency);
    }

    /**
     * Get the inverse exchange rate
     */
    public function inverse(): self
    {
        $inverseRate = bcdiv('1', $this->rate, self::PRECISION);
        return new self($this->toCurrency, $this->fromCurrency, $inverseRate, $this->effectiveDate);
    }

    /**
     * Check if this rate is valid for a specific date
     */
    public function isValidFor(DateTimeImmutable $date): bool
    {
        return $date >= $this->effectiveDate;
    }

    public function __toString(): string
    {
        return sprintf(
            '1 %s = %s %s (effective %s)',
            $this->fromCurrency,
            $this->rate,
            $this->toCurrency,
            $this->effectiveDate->format('Y-m-d')
        );
    }

    private const ISO_CURRENCY_LENGTH = 3;
    private const BASE_SUFFIX_LENGTH = 5; // '_BASE'
    private const IDENTITY_CURRENCY_LENGTH = 8; // 3 + 5 = 'XXX_BASE'

    private function validateCurrency(string $currency): void
    {
        $length = strlen($currency);
        
        // Allow standard 3-letter ISO currency codes
        // Or 8-character identity codes (e.g., 'USD_BASE' for identity conversion)
        if ($length !== self::ISO_CURRENCY_LENGTH && $length !== self::IDENTITY_CURRENCY_LENGTH) {
            throw new InvalidArgumentException("Currency must be 3-letter ISO code, got: {$currency}");
        }

        // Validate identity currency format
        if ($length === self::IDENTITY_CURRENCY_LENGTH) {
            if (!str_ends_with($currency, '_BASE')) {
                throw new InvalidArgumentException(
                    "Extended currency code must end with '_BASE' suffix: {$currency}"
                );
            }
        }

        if (!ctype_upper(substr($currency, 0, self::ISO_CURRENCY_LENGTH))) {
            throw new InvalidArgumentException("Currency code must be uppercase: {$currency}");
        }
    }

    private function validateRate(string $rate): void
    {
        if (!is_numeric($rate)) {
            throw new InvalidArgumentException("Exchange rate must be numeric, got: {$rate}");
        }

        if (bccomp($rate, '0', self::PRECISION) <= 0) {
            throw new InvalidArgumentException('Exchange rate must be positive');
        }
    }
}
