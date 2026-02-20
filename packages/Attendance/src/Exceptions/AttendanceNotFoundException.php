<?php

declare(strict_types=1);

namespace Nexus\Attendance\Exceptions;

/**
 * Thrown when attendance record is not found
 */
final class AttendanceNotFoundException extends AttendanceException
{
    public function __construct(string $attendanceId)
    {
        parent::__construct("Attendance record not found: {$attendanceId}");
    }
}
