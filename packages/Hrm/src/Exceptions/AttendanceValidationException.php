<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class AttendanceValidationException extends HrmException
{
    public static function invalidStatus(string $status): self
    {
        return new self("Invalid attendance status: '{$status}'.");
    }
    
    public static function clockOutBeforeClockIn(): self
    {
        return new self("Clock out time cannot be before clock in time.");
    }
    
    public static function alreadyClockedIn(string $employeeId): self
    {
        return new self("Employee '{$employeeId}' has already clocked in today.");
    }
    
    public static function notClockedIn(string $employeeId): self
    {
        return new self("Employee '{$employeeId}' has not clocked in today.");
    }
    
    public static function missingRequiredField(string $field): self
    {
        return new self("Required field '{$field}' is missing.");
    }
}
