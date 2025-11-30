<?php

declare(strict_types=1);

namespace Nexus\Period\Exceptions;

/**
 * Thrown when a requested period is not found
 */
final class PeriodNotFoundException extends PeriodException
{
    public static function forId(string $id): self
    {
        return new self("Period not found with ID: {$id}");
    }
}
