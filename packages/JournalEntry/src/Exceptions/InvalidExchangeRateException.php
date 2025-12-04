<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Exceptions;

/**
 * Thrown when an exchange rate is invalid.
 */
class InvalidExchangeRateException extends JournalEntryException
{
    public static function invalidCurrency(string $currency): self
    {
        return new self("Invalid currency code: {$currency}. Must be 3 uppercase letters (ISO 4217)");
    }

    public static function invalidRate(string $rate): self
    {
        return new self("Invalid exchange rate: {$rate}. Must be a numeric value");
    }

    public static function rateMustBePositive(): self
    {
        return new self('Exchange rate must be a positive value');
    }

    public static function currencyMismatch(string $expected, string $actual): self
    {
        return new self("Currency mismatch: expected {$expected}, got {$actual}");
    }

    public static function notFound(string $fromCurrency, string $toCurrency, string $date): self
    {
        return new self("Exchange rate not found: {$fromCurrency}/{$toCurrency} on {$date}");
    }
}
