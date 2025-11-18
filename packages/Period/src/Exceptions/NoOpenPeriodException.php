<?php

declare(strict_types=1);

namespace Nexus\Period\Exceptions;

/**
 * Thrown when no open period exists for a type
 */
final class NoOpenPeriodException extends PeriodException
{
    public static function forType(string $typeName): self
    {
        return new self("No open period found for type: {$typeName}");
    }
}
