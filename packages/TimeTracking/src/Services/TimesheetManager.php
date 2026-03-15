<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\Services;

use Nexus\TimeTracking\Contracts\TimesheetManagerInterface;
use Nexus\TimeTracking\Contracts\TimesheetPersistInterface;
use Nexus\TimeTracking\Contracts\TimesheetQueryInterface;
use Nexus\TimeTracking\Enums\TimesheetStatus;
use Nexus\TimeTracking\Exceptions\TimesheetImmutableException;
use Nexus\TimeTracking\ValueObjects\TimesheetSummary;

/**
 * Timesheet submit with validation (BUS-PRO-0056, BUS-PRO-0063).
 */
final readonly class TimesheetManager implements TimesheetManagerInterface
{
    public function __construct(
        private TimesheetQueryInterface $query,
        private TimesheetPersistInterface $persist,
        private HoursValidator $hoursValidator,
    ) {
    }

    public function submit(TimesheetSummary $timesheet): void
    {
        if ($timesheet->isApproved()) {
            throw TimesheetImmutableException::cannotEdit($timesheet->id);
        }
        $this->hoursValidator->validateEntry($timesheet->hours);
        $existing = $this->query->getTotalHoursByUserAndDate($timesheet->userId, $timesheet->date, $timesheet->id);
        $this->hoursValidator->validateDailyTotal($existing, $timesheet->hours);
        $this->persist->persist($timesheet);
    }
}
