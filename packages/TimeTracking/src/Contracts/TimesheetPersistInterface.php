<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\Contracts;

use Nexus\TimeTracking\ValueObjects\TimesheetSummary;

/**
 * Timesheet persistence (write side).
 */
interface TimesheetPersistInterface
{
    public function persist(TimesheetSummary $timesheet): void;
}
