<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class AttendanceNotFoundException extends HrmException
{
    public static function forId(string $id): self
    {
        return new self("Attendance record with ID '{$id}' not found.");
    }
}
