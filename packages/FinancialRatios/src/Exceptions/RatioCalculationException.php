<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Exceptions;

/**
 * Base exception for ratio calculation errors.
 */
class RatioCalculationException extends \RuntimeException
{
    /**
     * Create exception for division by zero.
     */
    public static function divisionByZero(string $ratioName, string $denominator): self
    {
        return new self(
            sprintf(
                'Cannot calculate %s: %s is zero.',
                $ratioName,
                $denominator
            )
        );
    }

    /**
     * Create exception for invalid input.
     */
    public static function invalidInput(string $ratioName, string $reason): self
    {
        return new self(
            sprintf(
                'Invalid input for %s: %s',
                $ratioName,
                $reason
            )
        );
    }

    /**
     * Create exception for insufficient data.
     */
    public static function insufficientData(string $ratioName): self
    {
        return new self(
            sprintf(
                'Insufficient data to calculate %s.',
                $ratioName
            )
        );
    }
}
