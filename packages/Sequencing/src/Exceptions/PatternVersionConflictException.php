<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Exceptions;

use Exception;

/**
 * Exception thrown when pattern version dates conflict.
 */
class PatternVersionConflictException extends Exception
{
    public static function overlappingDates(\DateTimeInterface $effectiveFrom, \DateTimeInterface $existingFrom): self
    {
        return new self(
            "Pattern version conflict: Effective date {$effectiveFrom->format('Y-m-d')} " .
            "overlaps with existing version starting {$existingFrom->format('Y-m-d')}"
        );
    }

    public static function retroactiveChange(\DateTimeInterface $effectiveFrom): self
    {
        return new self(
            "Cannot create retroactive pattern version with effective date {$effectiveFrom->format('Y-m-d')}. " .
            "Pattern is already in use."
        );
    }
}
