<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Adapters;

use Nexus\ProjectManagementOperations\Contracts\AttendanceQueryInterface;
use Nexus\TimeTracking\Contracts\TimesheetQueryInterface;
use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectTaskIdsQueryInterface;

/**
 * Implements orchestrator AttendanceQueryInterface using TimeTracking and project task resolution.
 * Task IDs are tenant-scoped via ProjectTaskIdsQueryInterface::getTaskIdsForProject($tenantId, $projectId).
 * The app must ensure the implementation of TimesheetQueryInterface is tenant-scoped (e.g. all reads
 * filtered by tenant) so getByWorkItem($workItemId) does not return timesheets from other tenants.
 */
final readonly class AttendanceQueryAdapter implements AttendanceQueryInterface
{
    public function __construct(
        private ProjectTaskIdsQueryInterface $taskIdsQuery,
        private TimesheetQueryInterface $timesheetQuery,
    ) {
    }

    public function getTotalHoursByProject(
        string $tenantId,
        string $projectId,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): float {
        $taskIds = $this->taskIdsQuery->getTaskIdsForProject($tenantId, $projectId);
        $total = 0.0;
        foreach ($taskIds as $workItemId) {
            $entries = $this->timesheetQuery->getByWorkItem($workItemId);
            foreach ($entries as $entry) {
                if ($entry->date >= $start && $entry->date <= $end && $entry->status->isImmutable()) {
                    $total += $entry->hours;
                }
            }
        }
        return $total;
    }
}
