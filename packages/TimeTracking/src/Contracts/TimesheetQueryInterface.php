<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\Contracts;

use DateTimeImmutable;
use Nexus\TimeTracking\ValueObjects\TimesheetSummary;

/**
 * Read-only timesheet query contract.
 */
interface TimesheetQueryInterface
{
    public function getById(string $timesheetId): ?TimesheetSummary;

    /**
     * Sum of approved hours for the given work item (BUS-PRO-0070: task actual = sum approved).
     */
    public function getApprovedHoursForWorkItem(string $workItemId): float;

    /**
     * Total hours already logged for user on date (for 24h/day validation).
     * When updating, pass excludeTimesheetId to exclude current record from total.
     */
    public function getTotalHoursByUserAndDate(string $userId, DateTimeImmutable $date, ?string $excludeTimesheetId = null): float;

    /**
     * @return list<TimesheetSummary>
     */
    public function getByWorkItem(string $workItemId): array;
}
