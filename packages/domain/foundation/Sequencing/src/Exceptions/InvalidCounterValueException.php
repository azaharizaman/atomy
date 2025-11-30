<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Exceptions;

use Exception;

/**
 * Exception thrown when an invalid counter value is provided.
 */
class InvalidCounterValueException extends Exception
{
    public static function mustBeGreaterThanCurrent(int $providedValue, int $currentValue): self
    {
        return new self(
            "Manual override value ({$providedValue}) must be greater than current counter value ({$currentValue})"
        );
    }

    public static function mustBePositive(): self
    {
        return new self("Counter value must be a positive integer");
    }
}
