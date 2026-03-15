<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Contracts;

interface AttendanceQueryInterface
{
    /**
     * Get total approved hours for a project within a period (tenant-scoped).
     */
    public function getTotalHoursByProject(
        string $tenantId,
        string $projectId,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): float;
}
