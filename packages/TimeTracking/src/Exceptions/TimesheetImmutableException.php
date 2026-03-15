<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\Exceptions;

/**
 * Thrown when modifying or deleting an approved timesheet (BUS-PRO-0063).
 */
final class TimesheetImmutableException extends TimeTrackingException
{
    public static function cannotEdit(string $timesheetId): self
    {
        return new self(sprintf('Approved timesheets are immutable; cannot edit %s.', $timesheetId));
    }
}
