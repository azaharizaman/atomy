<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Models\Task as TaskModel;
use Nexus\Task\Contracts\TaskQueryInterface;
use Nexus\Task\Enums\TaskPriority;
use Nexus\Task\Enums\TaskStatus;
use Nexus\Task\ValueObjects\TaskSummary;
use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class AtomyTaskQuery implements TaskQueryInterface
{
    public function __construct(private TenantContextInterface $tenantContext)
    {
    }

    public function getById(string $taskId): ?TaskSummary
    {
        $tenantId = $this->tenantContext->requireTenant();
        $row = TaskModel::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $taskId)
            ->first();

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
        $tenantId = $this->tenantContext->requireTenant();
        $row = TaskModel::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $taskId)
            ->first();

        return $row === null ? [] : ($row->predecessor_ids ?? []);
    }

    public function getByAssignee(string $assigneeId): array
    {
        $tenantId = $this->tenantContext->requireTenant();
        return TaskModel::query()
            ->where('tenant_id', $tenantId)
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
