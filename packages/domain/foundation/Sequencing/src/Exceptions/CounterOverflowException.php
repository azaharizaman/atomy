<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Exceptions;

use Exception;

/**
 * Exception thrown when counter overflow occurs.
 */
class CounterOverflowException extends Exception
{
    public static function maxValueExceeded(string $sequenceName, int $currentValue, int $maxValue): self
    {
        return new self(
            "Counter overflow for sequence '{$sequenceName}'. Current: {$currentValue}, Max: {$maxValue}"
        );
    }
}
