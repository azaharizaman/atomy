<?php

declare(strict_types=1);

namespace Nexus\Currency\Exceptions;

use DateTimeImmutable;
use Exception;
use Nexus\Currency\ValueObjects\CurrencyPair;

/**
 * Exchange Rate Not Found Exception
 *
 * Thrown when an exchange rate cannot be found for a currency pair.
 *
 * @package Nexus\Currency\Exceptions
 */
class ExchangeRateNotFoundException extends Exception
{
    public function __construct(string $message = 'Exchange rate not found', int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for rate not found for a currency pair
     */
    public static function forPair(CurrencyPair $pair, ?DateTimeImmutable $date = null): self
    {
        $dateStr = $date ? " as of {$date->format('Y-m-d')}" : '';
        return new self(
            "Exchange rate not found for currency pair {$pair->toString()}{$dateStr}. " .
            "Ensure the provider supports this pair and historical rates if applicable."
        );
    }

    /**
     * Create exception for rate not found for specific currencies
     */
    public static function forCurrencies(string $fromCode, string $toCode, ?DateTimeImmutable $date = null): self
    {
        $dateStr = $date ? " as of {$date->format('Y-m-d')}" : '';
        return new self(
            "Exchange rate not found from {$fromCode} to {$toCode}{$dateStr}."
        );
    }

    /**
     * Create exception when historical rates are not supported
     */
    public static function historicalNotSupported(CurrencyPair $pair, DateTimeImmutable $date): self
    {
        return new self(
            "Historical exchange rates are not supported by the current provider for {$pair->toString()} " .
            "as of {$date->format('Y-m-d')}.",
            501 // Not Implemented
        );
    }

    /**
     * Create exception when rate is too old/unavailable
     */
    public static function dateOutOfRange(CurrencyPair $pair, DateTimeImmutable $date): self
    {
        return new self(
            "Exchange rate for {$pair->toString()} is not available for {$date->format('Y-m-d')}. " .
            "The requested date may be outside the provider's data range."
        );
    }
}
