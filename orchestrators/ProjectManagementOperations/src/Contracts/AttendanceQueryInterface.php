<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Contracts;

interface AttendanceQueryInterface
{
    /**
     * Get total actual hours for a project within a period
     */
    public function getTotalHoursByProject(
        string $projectId,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): float;
}
