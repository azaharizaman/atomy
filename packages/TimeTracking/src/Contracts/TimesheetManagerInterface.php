<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\Contracts;

use Nexus\TimeTracking\ValueObjects\TimesheetSummary;

/**
 * Timesheet lifecycle and business operations (FUN-PRO-0254, FUN-PRO-0566).
 * Validates hours (BUS-PRO-0056) and daily total per user.
 */
interface TimesheetManagerInterface
{
    /**
     * Submit a new or updated timesheet. Validates hours and daily cap.
     *
     * @throws \Nexus\TimeTracking\Exceptions\InvalidHoursException
     * @throws \Nexus\TimeTracking\Exceptions\TimesheetImmutableException If entry is already approved.
     */
    public function submit(TimesheetSummary $timesheet): void;
}
