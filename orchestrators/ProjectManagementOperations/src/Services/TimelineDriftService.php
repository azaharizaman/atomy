<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Services;

use Nexus\ProjectManagementOperations\Contracts\ProjectQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\SchedulerQueryInterface;
use Nexus\ProjectManagementOperations\DTOs\TimelineHealthDTO;

final readonly class TimelineDriftService
{
    public function __construct(
        private ProjectQueryInterface $projectQuery,
        private SchedulerQueryInterface $schedulerQuery
    ) {
    }

    public function calculate(string $projectId, ?\DateTimeImmutable $now = null): TimelineHealthDTO
    {
        $now ??= new \DateTimeImmutable();
        $milestones = $this->projectQuery->getMilestones($projectId);
        
        $total = count($milestones);
        $completed = 0;
        $delayed = 0;
        $driftDetails = [];

        foreach ($milestones as $milestone) {
            if ($milestone->completedAt !== null) {
                $completed++;
                if ($milestone->completedAt > $milestone->dueDate) {
                    $delayed++;
                    $driftDetails[] = [
                        'id' => $milestone->id,
                        'name' => $milestone->name,
                        'due_date' => $milestone->dueDate->format('Y-m-d'),
                        'completed_at' => $milestone->completedAt->format('Y-m-d'),
                        'drift_days' => $milestone->completedAt->diff($milestone->dueDate)->days
                    ];
                }
            } elseif ($now > $milestone->dueDate) {
                // Also count pending milestones that are past their due date
                $delayed++;
                $driftDetails[] = [
                    'id' => $milestone->id,
                    'name' => $milestone->name,
                    'due_date' => $milestone->dueDate->format('Y-m-d'),
                    'completed_at' => null,
                    'drift_days' => $now->diff($milestone->dueDate)->days
                ];
            }
        }

        $completionPercentage = $total > 0 ? ($completed / $total) * 100 : 0.0;

        return new TimelineHealthDTO(
            projectId: $projectId,
            totalMilestones: $total,
            completedMilestones: $completed,
            delayedMilestones: $delayed,
            completionPercentage: (float) $completionPercentage,
            driftDetails: $driftDetails
        );
    }
}
