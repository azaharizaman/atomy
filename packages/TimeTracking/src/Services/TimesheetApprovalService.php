<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\Services;

use Nexus\TimeTracking\Contracts\TimesheetApprovalInterface;
use Nexus\TimeTracking\Contracts\TimesheetPersistInterface;
use Nexus\TimeTracking\Contracts\TimesheetQueryInterface;
use Nexus\TimeTracking\Enums\TimesheetStatus;
use Nexus\TimeTracking\Exceptions\TimesheetImmutableException;
use Nexus\TimeTracking\ValueObjects\TimesheetSummary;

/**
 * Approval workflow (FUN-PRO-0575). Approved timesheets are immutable (BUS-PRO-0063).
 */
final readonly class TimesheetApprovalService implements TimesheetApprovalInterface
{
    public function __construct(
        private TimesheetQueryInterface $query,
        private TimesheetPersistInterface $persist,
    ) {
    }

    public function approve(string $timesheetId): void
    {
        $timesheet = $this->query->getById($timesheetId);
        if ($timesheet === null) {
            return;
        }
        if ($timesheet->isApproved()) {
            throw TimesheetImmutableException::cannotEdit($timesheetId);
        }
        $approved = new TimesheetSummary(
            $timesheet->id,
            $timesheet->userId,
            $timesheet->workItemId,
            $timesheet->date,
            $timesheet->hours,
            $timesheet->description,
            TimesheetStatus::Approved,
            $timesheet->billingRate,
        );
        $this->persist->persist($approved);
    }

    public function reject(string $timesheetId, string $reason = ''): void
    {
        $timesheet = $this->query->getById($timesheetId);
        if ($timesheet === null) {
            return;
        }
        if ($timesheet->isApproved()) {
            throw TimesheetImmutableException::cannotEdit($timesheetId);
        }
        $rejected = new TimesheetSummary(
            $timesheet->id,
            $timesheet->userId,
            $timesheet->workItemId,
            $timesheet->date,
            $timesheet->hours,
            $timesheet->description,
            TimesheetStatus::Rejected,
            $timesheet->billingRate,
        );
        $this->persist->persist($rejected);
    }
}
