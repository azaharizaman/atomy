<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\Contracts;

/**
 * Timesheet approval workflow (FUN-PRO-0575).
 * Approved timesheets are immutable (BUS-PRO-0063).
 */
interface TimesheetApprovalInterface
{
    /**
     * Approve a timesheet by id. Caller must enforce permission (BUS-PRO-0125).
     *
     * @throws \Nexus\TimeTracking\Exceptions\TimesheetImmutableException If already approved.
     */
    public function approve(string $timesheetId): void;

    /**
     * Reject a submitted timesheet.
     *
     * @throws \Nexus\TimeTracking\Exceptions\TimesheetImmutableException If already approved.
     */
    public function reject(string $timesheetId, string $reason = ''): void;
}
