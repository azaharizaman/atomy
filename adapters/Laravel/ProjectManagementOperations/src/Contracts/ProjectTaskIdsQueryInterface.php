<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Contracts;

/**
 * Resolves task IDs for a project. Implemented by the application (e.g. from DB).
 * Used by AttendanceQueryAdapter to sum timesheet hours by project.
 */
interface ProjectTaskIdsQueryInterface
{
    /**
     * @return list<string> Task IDs belonging to the project
     */
    public function getTaskIdsForProject(string $projectId): array;
}
