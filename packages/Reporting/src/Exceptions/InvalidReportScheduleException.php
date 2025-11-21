<?php

declare(strict_types=1);

namespace Nexus\Reporting\Exceptions;

/**
 * Thrown when a report schedule configuration is invalid.
 */
class InvalidReportScheduleException extends ReportingException
{
    /**
     * Create exception for invalid cron expression.
     */
    public static function invalidCronExpression(string $expression): self
    {
        return new self(
            "Invalid cron expression: '{$expression}'"
        );
    }

    /**
     * Create exception for invalid date range.
     */
    public static function invalidDateRange(
        \DateTimeImmutable $startsAt,
        \DateTimeImmutable $endsAt
    ): self {
        return new self(
            "Invalid date range: start date '{$startsAt->format('Y-m-d H:i:s')}' must be before end date '{$endsAt->format('Y-m-d H:i:s')}'"
        );
    }

    /**
     * Create exception for missing required field.
     */
    public static function missingField(string $field, string $scheduleType): self
    {
        return new self(
            "Field '{$field}' is required for schedule type '{$scheduleType}'"
        );
    }

    /**
     * Create exception for invalid interval.
     */
    public static function invalidInterval(int $interval, string $scheduleType): self
    {
        return new self(
            "Invalid interval '{$interval}' for schedule type '{$scheduleType}'. Must be positive integer."
        );
    }

    /**
     * Create exception for schedule in the past.
     */
    public static function scheduleInPast(\DateTimeImmutable $scheduledAt): self
    {
        return new self(
            "Cannot schedule report in the past: '{$scheduledAt->format('Y-m-d H:i:s')}'"
        );
    }
}
