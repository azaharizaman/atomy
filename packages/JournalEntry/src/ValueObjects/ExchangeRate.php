<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\JournalEntry\Exceptions\InvalidExchangeRateException;

/**
 * Exchange Rate Value Object.
 *
 * Represents an exchange rate between two currencies at a specific effective date.
 * Used for multi-currency journal entry conversion.
 *
 * Immutable: all properties are readonly.
 */
final readonly class ExchangeRate
{
    /**
     * Default scale for rate calculations.
     */
    private const int SCALE = 8;

    /**
     * Create a new ExchangeRate instance.
     *
     * @param string $fromCurrency Source currency code (ISO 4217)
     * @param string $toCurrency Target currency code (ISO 4217)
     * @param string $rate Exchange rate (multiply source by this to get target)
     * @param \DateTimeImmutable $effectiveDate Date this rate is effective
     */
    public function __construct(
        public string $fromCurrency,
        public string $toCurrency,
        public string $rate,
        public \DateTimeImmutable $effectiveDate
    ) {
        // Validate currency codes
        if (!preg_match('/^[A-Z]{3}$/', $fromCurrency)) {
            throw InvalidExchangeRateException::invalidCurrency($fromCurrency);
        }

        if (!preg_match('/^[A-Z]{3}$/', $toCurrency)) {
            throw InvalidExchangeRateException::invalidCurrency($toCurrency);
        }

        // Validate rate is numeric and positive
        if (!is_numeric($rate)) {
            throw InvalidExchangeRateException::invalidRate($rate);
        }

        if (bccomp($rate, '0', self::SCALE) <= 0) {
            throw InvalidExchangeRateException::rateMustBePositive();
        }
    }

    /**
     * Create exchange rate from components.
     *
     * @param string $fromCurrency Source currency code (ISO 4217)
     * @param string $toCurrency Target currency code (ISO 4217)
     * @param string $rate Exchange rate value
     * @param \DateTimeImmutable $effectiveDate Date this rate is effective
     */
    public static function of(
        string $fromCurrency,
        string $toCurrency,
        string $rate,
        \DateTimeImmutable $effectiveDate
    ): self {
        return new self(
            $fromCurrency,
            $toCurrency,
            $rate,
            $effectiveDate
        );
    }

    /**
     * Create identity rate (1:1 for same currency).
     *
     * @param string $currency Currency code (ISO 4217)
     * @param \DateTimeImmutable $effectiveDate Date this rate is effective
     */
    public static function identity(string $currency, \DateTimeImmutable $effectiveDate): self
    {
        return new self(
            $currency,
            $currency,
            '1',
            $effectiveDate
        );
    }

    /**
     * Convert an amount using this exchange rate.
     *
     * Uses high-precision string-based conversion to prevent precision loss.
     *
     * @param Money $amount Source amount (must match fromCurrency)
     * @return Money Converted amount in toCurrency
     * @throws InvalidExchangeRateException If currencies don't match
     */
    public function convert(Money $amount): Money
    {
        if ($amount->getCurrency() !== $this->fromCurrency) {
            throw InvalidExchangeRateException::currencyMismatch(
                $amount->getCurrency(),
                $this->fromCurrency
            );
        }

        // Use string-based conversion to maintain precision
        return $amount->convertToCurrencyWithStringRate($this->toCurrency, $this->rate, self::SCALE);
    }

    /**
     * Get the inverse rate (for reverse conversion).
     */
    public function inverse(): self
    {
        $inverseRate = bcdiv('1', $this->rate, self::SCALE);

        return new self(
            $this->toCurrency,
            $this->fromCurrency,
            $inverseRate,
            $this->effectiveDate
        );
    }

    /**
     * Check if this rate is for same-currency (identity rate).
     */
    public function isSameCurrency(): bool
    {
        return $this->fromCurrency === $this->toCurrency;
    }

    /**
     * Check if this rate is effective on a given date.
     */
    public function isEffectiveOn(\DateTimeImmutable $date): bool
    {
        return $date->format('Y-m-d') === $this->effectiveDate->format('Y-m-d');
    }

    /**
     * Get rate as float (use with caution - precision loss possible).
     */
    public function toFloat(): float
    {
        return (float) $this->rate;
    }

    /**
     * Get string representation.
     */
    public function toString(): string
    {
        return "{$this->fromCurrency}/{$this->toCurrency} = {$this->rate}";
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
