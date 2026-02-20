<?php

declare(strict_types=1);

namespace Nexus\Attendance\Exceptions;

/**
 * Thrown when check time is invalid (e.g., checking out before checking in)
 */
final class InvalidCheckTimeException extends AttendanceException
{
    public function __construct(string $message = 'Invalid check time')
    {
        parent::__construct($message);
    }

    public static function checkOutBeforeCheckIn(): self
    {
        return new self('Cannot check out before checking in');
    }

    public static function alreadyCheckedIn(): self
    {
        return new self('Employee already checked in for today');
    }

    public static function notCheckedIn(): self
    {
        return new self('Employee has not checked in yet');
    }
}
