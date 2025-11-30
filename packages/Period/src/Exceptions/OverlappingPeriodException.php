<?php

declare(strict_types=1);

namespace Nexus\Period\Exceptions;

/**
 * Thrown when attempting to create a period that overlaps with an existing one
 */
final class OverlappingPeriodException extends PeriodException
{
    public static function create(string $startDate, string $endDate): self
    {
        return new self("Period with dates {$startDate} to {$endDate} overlaps with an existing period");
    }
}
