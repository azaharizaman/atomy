<?php

declare(strict_types=1);

namespace Nexus\Currency\Exceptions;

use Exception;
use Throwable;

/**
 * Exchange Rate Provider Exception
 *
 * Thrown when an external exchange rate provider encounters an error.
 *
 * @package Nexus\Currency\Exceptions
 */
class ExchangeRateProviderException extends Exception
{
    public function __construct(string $message = 'Exchange rate provider error', int $code = 502, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for provider API failure
     */
    public static function apiFailure(string $providerName, ?Throwable $previous = null): self
    {
        return new self(
            "Exchange rate provider '{$providerName}' API failed. Please try again later.",
            502,
            $previous
        );
    }

    /**
     * Create exception for provider authentication failure
     */
    public static function authenticationFailed(string $providerName): self
    {
        return new self(
            "Authentication failed for exchange rate provider '{$providerName}'. Check API credentials.",
            401
        );
    }

    /**
     * Create exception for rate limit exceeded
     */
    public static function rateLimitExceeded(string $providerName, ?int $retryAfter = null): self
    {
        $retryMsg = $retryAfter ? " Retry after {$retryAfter} seconds." : '';
        return new self(
            "Rate limit exceeded for exchange rate provider '{$providerName}'.{$retryMsg}",
            429
        );
    }

    /**
     * Create exception for invalid provider response
     */
    public static function invalidResponse(string $providerName, string $reason = ''): self
    {
        $reasonMsg = $reason ? ": {$reason}" : '';
        return new self(
            "Invalid response from exchange rate provider '{$providerName}'{$reasonMsg}",
            502
        );
    }

    /**
     * Create exception for provider unavailability
     */
    public static function unavailable(string $providerName): self
    {
        return new self(
            "Exchange rate provider '{$providerName}' is currently unavailable.",
            503
        );
    }

    /**
     * Create exception for network/connection errors
     */
    public static function connectionFailed(string $providerName, ?Throwable $previous = null): self
    {
        return new self(
            "Failed to connect to exchange rate provider '{$providerName}'.",
            503,
            $previous
        );
    }
}
