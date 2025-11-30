<?php

declare(strict_types=1);

namespace Nexus\Currency\Exceptions;

use Exception;

/**
 * Incompatible Currency Exception
 *
 * Thrown when attempting operations on incompatible currencies (already handled by Money VO in Finance package,
 * but provided here for Currency package operations).
 *
 * @package Nexus\Currency\Exceptions
 */
class IncompatibleCurrencyException extends Exception
{
    public function __construct(string $message = 'Incompatible currencies', int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for currency mismatch in operations
     */
    public static function forOperation(string $currency1, string $currency2, string $operation = 'operation'): self
    {
        return new self(
            "Cannot perform {$operation} on incompatible currencies: {$currency1} and {$currency2}. " .
            "Convert to same currency first using exchange rate conversion."
        );
    }

    /**
     * Create exception for same currency pair
     */
    public static function sameCurrency(string $currency): self
    {
        return new self(
            "Cannot create currency pair with same currency: {$currency}/{$currency}"
        );
    }
}
