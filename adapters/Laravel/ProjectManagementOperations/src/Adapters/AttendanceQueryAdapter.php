<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Adapters;

use Nexus\ProjectManagementOperations\Contracts\AttendanceQueryInterface;
use Nexus\TimeTracking\Contracts\TimesheetQueryInterface;
use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectTaskIdsQueryInterface;

/**
 * Implements orchestrator AttendanceQueryInterface using TimeTracking and project task resolution.
 */
final readonly class AttendanceQueryAdapter implements AttendanceQueryInterface
{
    public function __construct(
        private ProjectTaskIdsQueryInterface $taskIdsQuery,
        private TimesheetQueryInterface $timesheetQuery,
    ) {
    }

    public function getTotalHoursByProject(
        string $projectId,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): float {
        $taskIds = $this->taskIdsQuery->getTaskIdsForProject($projectId);
        $total = 0.0;
        foreach ($taskIds as $workItemId) {
            $entries = $this->timesheetQuery->getByWorkItem($workItemId);
            foreach ($entries as $entry) {
                if ($entry->date >= $start && $entry->date <= $end) {
                    $total += $entry->hours;
                }
            }
        }
        return $total;
    }
}
