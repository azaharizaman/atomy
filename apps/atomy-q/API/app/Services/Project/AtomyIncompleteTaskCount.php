<?php

declare(strict_types=1);

namespace App\Services\Project;

use App\Models\Task;
use Nexus\Project\Contracts\IncompleteTaskCountInterface;

final class AtomyIncompleteTaskCount implements IncompleteTaskCountInterface
{
    public function getIncompleteTaskCount(string $projectId): int
    {
        return Task::query()
            ->where('project_id', $projectId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
    }
}
