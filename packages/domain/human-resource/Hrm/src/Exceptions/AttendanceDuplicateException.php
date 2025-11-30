<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class AttendanceDuplicateException extends HrmException
{
    public static function forEmployeeAndDate(string $employeeId, string $date): self
    {
        return new self("Attendance record already exists for employee '{$employeeId}' on {$date}.");
    }
}
