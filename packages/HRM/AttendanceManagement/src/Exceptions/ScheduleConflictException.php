<?php

declare(strict_types=1);

namespace Nexus\AttendanceManagement\Exceptions;

/**
 * Thrown when schedule conflict detected
 */
final class ScheduleConflictException extends AttendanceException
{
    public function __construct(string $message = 'Schedule conflict detected')
    {
        parent::__construct($message);
    }

    public static function overlappingSchedules(string $scheduleId1, string $scheduleId2): self
    {
        return new self("Overlapping schedules detected: {$scheduleId1} and {$scheduleId2}");
    }
}
