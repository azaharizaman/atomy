<?php

declare(strict_types=1);

namespace Nexus\Project\Contracts;

/**
 * Used to enforce BUS-PRO-0096: project status cannot be "completed" if there are incomplete tasks.
 * Implemented by adapter/orchestrator (e.g. using Nexus\Task).
 */
interface IncompleteTaskCountInterface
{
    public function getIncompleteTaskCount(string $projectId): int;
}
