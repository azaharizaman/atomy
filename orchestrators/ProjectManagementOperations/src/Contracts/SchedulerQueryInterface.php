<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Contracts;

interface SchedulerQueryInterface
{
    /**
     * Get project's scheduled start and end dates
     * @return array{start: \DateTimeImmutable, end: \DateTimeImmutable}|null
     */
    public function getScheduledDates(string $projectId): ?array;
}
