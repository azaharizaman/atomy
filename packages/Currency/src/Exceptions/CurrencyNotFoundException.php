<?php

declare(strict_types=1);

namespace Nexus\Currency\Exceptions;

use Exception;

/**
 * Currency Not Found Exception
 *
 * Thrown when a currency cannot be found by the specified criteria.
 *
 * @package Nexus\Currency\Exceptions
 */
class CurrencyNotFoundException extends Exception
{
    public function __construct(string $message = 'Currency not found', int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for currency not found by code
     */
    public static function byCode(string $code): self
    {
        return new self("Currency with code '{$code}' not found. Ensure it exists in the currency repository.");
    }

    /**
     * Create exception for currency not found by numeric code
     */
    public static function byNumericCode(string $numericCode): self
    {
        return new self("Currency with numeric code '{$numericCode}' not found.");
    }

    /**
     * Create exception for invalid code format
     */
    public static function invalidFormat(string $code): self
    {
        return new self(
            "Invalid currency code format: '{$code}'. Must be 3-letter uppercase ISO 4217 code (e.g., USD, EUR, JPY).",
            400
        );
    }
}
