<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\Enums;

/**
 * Timesheet lifecycle status (FUN-PRO-0575).
 * Approved state is immutable (BUS-PRO-0063).
 */
enum TimesheetStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function isImmutable(): bool
    {
        return $this === self::Approved;
    }

    public function canEdit(): bool
    {
        return $this === self::Draft;
    }
}
