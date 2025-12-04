<?php

declare(strict_types=1);

namespace Nexus\AttendanceManagement\Exceptions;

/**
 * Thrown when work schedule is not found
 */
final class WorkScheduleNotFoundException extends AttendanceException
{
    public function __construct(string $scheduleId)
    {
        parent::__construct("Work schedule not found: {$scheduleId}");
    }

    public static function forEmployee(string $employeeId, \DateTimeImmutable $date): self
    {
        return new self("No work schedule found for employee {$employeeId} on {$date->format('Y-m-d')}");
    }
}
