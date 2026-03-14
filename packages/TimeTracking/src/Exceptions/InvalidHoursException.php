<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\Exceptions;

/**
 * Thrown when hours are negative or exceed 24h/day/user (BUS-PRO-0056).
 */
final class InvalidHoursException extends TimeTrackingException
{
    public static function negative(float $hours): self
    {
        return new self(sprintf('Timesheet hours cannot be negative, got %s.', $hours));
    }

    public static function exceedsMax(float $hours, float $max): self
    {
        return new self(sprintf('Timesheet hours cannot exceed %s per day, got %s.', $max, $hours));
    }

    public static function dailyTotalExceeded(float $total, float $max): self
    {
        return new self(sprintf('Total hours per day cannot exceed %s, would be %s.', $max, $total));
    }
}
