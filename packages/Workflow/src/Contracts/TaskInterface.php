<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Represents a user task in a workflow.
 *
 * Tasks are created when a workflow enters a "User Task" state
 * and require human action to proceed.
 */
interface TaskInterface
{
    /**
     * Get the task unique identifier.
     */
    public function getId(): string;

    /**
     * Get the associated workflow instance ID.
     */
    public function getWorkflowId(): string;

    /**
     * Get the workflow state that created this task.
     */
    public function getStateName(): string;

    /**
     * Get the task title/label.
     */
    public function getTitle(): string;

    /**
     * Get the task description.
     */
    public function getDescription(): ?string;

    /**
     * Get the assigned user ID (null if assigned to role).
     */
    public function getAssignedUserId(): ?string;

    /**
     * Get the assigned role name (null if assigned to user).
     */
    public function getAssignedRole(): ?string;

    /**
     * Get the task status (pending, completed, cancelled).
     */
    public function getStatus(): string;

    /**
     * Get the task priority (low, medium, high, critical).
     */
    public function getPriority(): string;

    /**
     * Get the task due date (null if no deadline).
     */
    public function getDueAt(): ?\DateTimeInterface;

    /**
     * Get task creation timestamp.
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * Get task completion timestamp (null if not completed).
     */
    public function getCompletedAt(): ?\DateTimeInterface;

    /**
     * Get the user who completed the task (null if not completed).
     */
    public function getCompletedBy(): ?string;

    /**
     * Get the action taken on the task (approve, reject, etc.).
     */
    public function getAction(): ?string;

    /**
     * Get the comment provided with the action.
     */
    public function getComment(): ?string;

    /**
     * Get task metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
}
