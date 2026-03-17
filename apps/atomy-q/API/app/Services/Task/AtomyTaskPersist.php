<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Models\Task as TaskModel;
use Nexus\Task\Contracts\TaskPersistInterface;
use Nexus\Task\Enums\TaskPriority;
use Nexus\Task\Enums\TaskStatus;
use Nexus\Task\ValueObjects\TaskSummary;

final class AtomyTaskPersist implements TaskPersistInterface
{
    public function persist(TaskSummary $task): void
    {
        $tenantId = request()?->attributes->get('auth_tenant_id');

        $attrs = [
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status->value,
            'priority' => $task->priority->value,
            'due_date' => $task->dueDate?->format('Y-m-d H:i:s'),
            'completed_at' => $task->completedAt?->format('Y-m-d H:i:s'),
            'assignee_ids' => $task->assigneeIds,
            'predecessor_ids' => $task->predecessorIds,
        ];
        if ($tenantId !== null && $tenantId !== '') {
            $attrs['tenant_id'] = $tenantId;
        }

        TaskModel::query()->updateOrCreate(
            ['id' => $task->id],
            $attrs
        );
    }

    public function delete(string $taskId): void
    {
        TaskModel::query()->where('id', $taskId)->delete();
    }
}
