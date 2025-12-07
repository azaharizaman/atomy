<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\ProcurementOperations\Enums\WorkflowStatus;

/**
 * Result of workflow execution.
 */
final readonly class WorkflowResult
{
    /**
     * @param string $instanceId Workflow instance identifier
     * @param string $workflowId Workflow definition identifier
     * @param WorkflowStatus $status Current status
     * @param string|null $currentStep Current step (if applicable)
     * @param array<string, mixed> $data Result data
     * @param string|null $errorMessage Error message if failed
     * @param array<string> $completedSteps List of completed steps
     */
    public function __construct(
        public string $instanceId,
        public string $workflowId,
        public WorkflowStatus $status,
        public ?string $currentStep = null,
        public array $data = [],
        public ?string $errorMessage = null,
        public array $completedSteps = [],
    ) {}

    /**
     * Check if workflow completed successfully.
     */
    public function isSuccessful(): bool
    {
        return $this->status === WorkflowStatus::COMPLETED;
    }

    /**
     * Check if workflow failed.
     */
    public function isFailed(): bool
    {
        return $this->status === WorkflowStatus::FAILED;
    }

    /**
     * Check if workflow is still in progress.
     */
    public function isInProgress(): bool
    {
        return !$this->status->isTerminal();
    }

    /**
     * Create a successful result.
     *
     * @param string $instanceId Workflow instance ID
     * @param string $workflowId Workflow definition ID
     * @param array<string, mixed> $data Result data
     * @param array<string> $completedSteps Completed steps
     */
    public static function success(
        string $instanceId,
        string $workflowId,
        array $data = [],
        array $completedSteps = [],
    ): self {
        return new self(
            instanceId: $instanceId,
            workflowId: $workflowId,
            status: WorkflowStatus::COMPLETED,
            currentStep: null,
            data: $data,
            errorMessage: null,
            completedSteps: $completedSteps,
        );
    }

    /**
     * Create a failed result.
     *
     * @param string $instanceId Workflow instance ID
     * @param string $workflowId Workflow definition ID
     * @param string $errorMessage Error message
     * @param string|null $failedStep Step that failed
     * @param array<string> $completedSteps Steps completed before failure
     */
    public static function failure(
        string $instanceId,
        string $workflowId,
        string $errorMessage,
        ?string $failedStep = null,
        array $completedSteps = [],
    ): self {
        return new self(
            instanceId: $instanceId,
            workflowId: $workflowId,
            status: WorkflowStatus::FAILED,
            currentStep: $failedStep,
            data: [],
            errorMessage: $errorMessage,
            completedSteps: $completedSteps,
        );
    }

    /**
     * Create a waiting result (workflow paused for external input).
     *
     * @param string $instanceId Workflow instance ID
     * @param string $workflowId Workflow definition ID
     * @param string $waitingStep Step waiting for input
     * @param array<string, mixed> $data Current data
     * @param array<string> $completedSteps Steps completed so far
     */
    public static function waiting(
        string $instanceId,
        string $workflowId,
        string $waitingStep,
        array $data = [],
        array $completedSteps = [],
    ): self {
        return new self(
            instanceId: $instanceId,
            workflowId: $workflowId,
            status: WorkflowStatus::WAITING,
            currentStep: $waitingStep,
            data: $data,
            errorMessage: null,
            completedSteps: $completedSteps,
        );
    }
}
