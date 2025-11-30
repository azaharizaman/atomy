<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Exceptions;

use Exception;

/**
 * Exception thrown when no active pattern version is found.
 */
class NoActivePatternException extends Exception
{
    public static function noActiveVersion(string $sequenceName, \DateTimeInterface $date): self
    {
        return new self(
            "No active pattern version found for sequence '{$sequenceName}' on {$date->format('Y-m-d')}"
        );
    }
}
