<?php

declare(strict_types=1);

namespace Nexus\Workflow\Services;

use Nexus\Workflow\Contracts\{
    TaskInterface,
    TaskRepositoryInterface,
    WorkflowRepositoryInterface
};
use Nexus\Workflow\ValueObjects\TaskAction;
use Nexus\Workflow\Exceptions\{TaskNotFoundException, UnauthorizedTaskActionException};

/**
 * Task Manager - Task operations service.
 *
 * Public API for task operations.
 */
final readonly class TaskManager
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private WorkflowRepositoryInterface $workflowRepository
    ) {}

    /**
     * Create a user task.
     */
    public function createTask(
        string $workflowId,
        string $stateName,
        string $title,
        ?string $assignedUserId = null,
        ?string $assignedRole = null,
        ?string $description = null,
        string $priority = 'medium',
        ?\DateTimeInterface $dueAt = null
    ): TaskInterface {
        // Validate workflow exists
        $workflow = $this->workflowRepository->findById($workflowId);
        
        // Create task - implementation in repository
        throw new \RuntimeException('Implementation required in repository layer');
    }

    /**
     * Complete a task with action.
     *
     * @throws TaskNotFoundException
     * @throws UnauthorizedTaskActionException
     */
    public function completeTask(
        string $taskId,
        string $userId,
        TaskAction $action,
        ?string $comment = null
    ): TaskInterface {
        $task = $this->taskRepository->findById($taskId);
        
        // Validate user can act on task
        $this->validateUserCanAct($task, $userId);
        
        // Update task status and action
        // Trigger workflow transition if needed
        // Record in history
        
        return $task;
    }

    /**
     * Delegate task to another user.
     */
    public function delegateTask(
        string $taskId,
        string $fromUserId,
        string $toUserId
    ): TaskInterface {
        $task = $this->taskRepository->findById($taskId);
        
        // Validate delegation permissions
        // Create delegation record
        // Update task assignment
        
        return $task;
    }

    /**
     * Validate user can act on task.
     *
     * @throws UnauthorizedTaskActionException
     */
    private function validateUserCanAct(TaskInterface $task, string $userId): void
    {
        // Check if user is assigned
        if ($task->getAssignedUserId() && $task->getAssignedUserId() !== $userId) {
            // Check delegation chain
            // If not authorized, throw exception
        }
        
        // Check self-approval rule (configurable)
        // Implementation depends on workflow configuration
    }
}
