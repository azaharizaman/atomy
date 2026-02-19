<?php

declare(strict_types=1);

namespace Nexus\Sales\Exceptions;

/**
 * Exception thrown when credit limit checking is unavailable.
 *
 * This exception is thrown by NullCreditLimitChecker when the
 * Receivable package is not installed or credit checking is disabled.
 */
final class CreditCheckUnavailableException extends SalesException
{
    /**
     * Create exception for unavailable credit check.
     */
    public static function unavailable(): self
    {
        return new self(
            'Credit limit checking is not available. ' .
            'This feature requires the Nexus\\Receivable package. ' .
            'Please install and configure Nexus\\Receivable to enable credit limit checking.'
        );
    }

    /**
     * Create exception for disabled credit check.
     */
    public static function disabled(): self
    {
        return new self(
            'Credit limit checking is disabled. ' .
            'Enable credit checking in configuration or install the Nexus\\Receivable package.'
        );
    }
}