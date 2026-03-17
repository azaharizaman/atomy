<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Models\Task as TaskModel;
use Nexus\Task\Contracts\TaskPersistInterface;
use Nexus\Task\ValueObjects\TaskSummary;
use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class AtomyTaskPersist implements TaskPersistInterface
{
    public function __construct(private TenantContextInterface $tenantContext)
    {
    }

    public function persist(TaskSummary $task): void
    {
        $tenantId = $this->tenantContext->requireTenant();

        $attrs = [
            'tenant_id' => $tenantId,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status->value,
            'priority' => $task->priority->value,
            'due_date' => $task->dueDate?->format('Y-m-d H:i:s'),
            'completed_at' => $task->completedAt?->format('Y-m-d H:i:s'),
            'assignee_ids' => $task->assigneeIds,
            'predecessor_ids' => $task->predecessorIds,
        ];

        TaskModel::query()->updateOrCreate(
            ['id' => $task->id, 'tenant_id' => $tenantId],
            $attrs
        );
    }

    public function delete(string $taskId): void
    {
        $tenantId = $this->tenantContext->requireTenant();
        TaskModel::query()->where('tenant_id', $tenantId)->where('id', $taskId)->delete();
    }
}
