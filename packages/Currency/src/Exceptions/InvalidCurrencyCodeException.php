<?php

declare(strict_types=1);

namespace Nexus\Currency\Exceptions;

use Exception;

/**
 * Invalid Currency Code Exception
 *
 * Thrown when a currency code fails validation against ISO 4217 standards.
 *
 * @package Nexus\Currency\Exceptions
 */
class InvalidCurrencyCodeException extends Exception
{
    public function __construct(string $message = 'Invalid currency code', int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for invalid code format
     */
    public static function forCode(string $code): self
    {
        return new self(
            "Invalid currency code: '{$code}'. Must be 3-letter uppercase ISO 4217 code (e.g., USD, EUR, JPY)."
        );
    }

    /**
     * Create exception for wrong length
     */
    public static function wrongLength(string $code, int $expectedLength = 3): self
    {
        return new self(
            "Currency code must be {$expectedLength} characters, got " . strlen($code) . ": '{$code}'"
        );
    }

    /**
     * Create exception for non-uppercase code
     */
    public static function notUppercase(string $code): self
    {
        return new self(
            "Currency code must be uppercase, got: '{$code}'. Use '" . strtoupper($code) . "' instead."
        );
    }

    /**
     * Create exception for non-alphabetic code
     */
    public static function notAlphabetic(string $code): self
    {
        return new self(
            "Currency code must contain only letters, got: '{$code}'"
        );
    }

    /**
     * Create exception for empty code
     */
    public static function empty(): self
    {
        return new self('Currency code cannot be empty.');
    }
}
