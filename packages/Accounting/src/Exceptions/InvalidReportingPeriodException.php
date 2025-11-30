<?php

declare(strict_types=1);

namespace Nexus\Accounting\Exceptions;

/**
 * Exception thrown when reporting period is invalid.
 */
final class InvalidReportingPeriodException extends \RuntimeException
{
    public static function periodNotFound(string $periodId): self
    {
        return new self("Reporting period '{$periodId}' not found.");
    }

    public static function invalidDateRange(\DateTimeImmutable $start, \DateTimeImmutable $end): self
    {
        return new self(
            "Invalid reporting period: start date {$start->format('Y-m-d')} is after end date {$end->format('Y-m-d')}"
        );
    }

    public static function periodNotAvailable(string $periodId, string $reason): self
    {
        return new self(
            "Reporting period '{$periodId}' is not available: {$reason}"
        );
    }

    public static function overlappingPeriods(string $period1, string $period2): self
    {
        return new self(
            "Reporting periods '{$period1}' and '{$period2}' overlap"
        );
    }

    public static function futureDate(\DateTimeImmutable $date): self
    {
        return new self(
            "Cannot create reporting period with future date: {$date->format('Y-m-d')}"
        );
    }
}
