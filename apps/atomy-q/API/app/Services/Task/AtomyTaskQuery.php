<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Models\Task as TaskModel;
use Nexus\Task\Contracts\TaskQueryInterface;
use Nexus\Task\Enums\TaskPriority;
use Nexus\Task\Enums\TaskStatus;
use Nexus\Task\ValueObjects\TaskSummary;

final class AtomyTaskQuery implements TaskQueryInterface
{
    public function getById(string $taskId): ?TaskSummary
    {
        $row = TaskModel::query()->find($taskId);

        if ($row === null) {
            return null;
        }

        return new TaskSummary(
            id: $row->id,
            title: $row->title,
            description: $row->description ?? '',
            status: TaskStatus::from($row->status),
            priority: TaskPriority::from($row->priority),
            dueDate: $row->due_date?->toDateTimeImmutable(),
            assigneeIds: $row->assignee_ids ?? [],
            predecessorIds: $row->predecessor_ids ?? [],
            completedAt: $row->completed_at?->toDateTimeImmutable(),
        );
    }

    public function getPredecessorIds(string $taskId): array
    {
        $row = TaskModel::query()->find($taskId);

        return $row === null ? [] : ($row->predecessor_ids ?? []);
    }

    public function getByAssignee(string $assigneeId): array
    {
        return TaskModel::query()
            ->whereJsonContains('assignee_ids', $assigneeId)
            ->get()
            ->map(fn (TaskModel $row) => new TaskSummary(
                id: $row->id,
                title: $row->title,
                description: $row->description ?? '',
                status: TaskStatus::from($row->status),
                priority: TaskPriority::from($row->priority),
                dueDate: $row->due_date?->toDateTimeImmutable(),
                assigneeIds: $row->assignee_ids ?? [],
                predecessorIds: $row->predecessor_ids ?? [],
                completedAt: $row->completed_at?->toDateTimeImmutable(),
            ))
            ->values()
            ->all();
    }
}
