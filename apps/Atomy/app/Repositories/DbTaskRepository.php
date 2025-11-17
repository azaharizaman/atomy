<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\WorkflowTask;
use Nexus\Workflow\Exceptions\TaskNotFoundException;
use Nexus\Workflow\Contracts\{TaskInterface, TaskRepositoryInterface};

/**
 * Eloquent implementation of TaskRepositoryInterface
 */
final readonly class DbTaskRepository implements TaskRepositoryInterface
{
    public function findById(string $id): TaskInterface
    {
        $task = WorkflowTask::find($id);
        
        if (!$task) {
            throw TaskNotFoundException::withId($id);
        }
        
        return $task;
    }

    public function findByWorkflow(string $workflowId): array
    {
        return WorkflowTask::where('workflow_id', $workflowId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function findPendingForUser(string $userId): array
    {
        return WorkflowTask::where('assigned_user_id', $userId)
            ->where('status', 'pending')
            ->orderBy('due_at', 'asc')
            ->get()
            ->all();
    }

    public function findPendingForRole(string $role): array
    {
        return WorkflowTask::where('assigned_role', $role)
            ->where('status', 'pending')
            ->orderBy('due_at', 'asc')
            ->get()
            ->all();
    }

    public function findOverdue(): array
    {
        return WorkflowTask::where('status', 'pending')
            ->where('due_at', '<', now())
            ->orderBy('due_at', 'asc')
            ->get()
            ->all();
    }

    public function save(TaskInterface $task): void
    {
        if ($task instanceof WorkflowTask) {
            $task->save();
        }
    }

    public function delete(string $id): void
    {
        WorkflowTask::destroy($id);
    }
}
