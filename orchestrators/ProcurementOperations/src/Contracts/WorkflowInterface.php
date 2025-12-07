<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\WorkflowContext;
use Nexus\ProcurementOperations\DTOs\WorkflowResult;

/**
 * Defines contract for stateful long-running workflows.
 *
 * Workflows track state across multiple steps and support
 * compensation (rollback) logic when failures occur.
 */
interface WorkflowInterface
{
    /**
     * Get the unique workflow identifier.
     */
    public function getId(): string;

    /**
     * Get the workflow name.
     */
    public function getName(): string;

    /**
     * Start the workflow with initial context.
     *
     * @param WorkflowContext $context Initial workflow context
     * @return WorkflowResult Result containing workflow state
     */
    public function start(WorkflowContext $context): WorkflowResult;

    /**
     * Resume a paused or waiting workflow.
     *
     * @param string $workflowInstanceId The instance to resume
     * @param array<string, mixed> $data Additional data for resumption
     * @return WorkflowResult Result after resumption
     */
    public function resume(string $workflowInstanceId, array $data = []): WorkflowResult;

    /**
     * Cancel a running workflow and trigger compensation.
     *
     * @param string $workflowInstanceId The instance to cancel
     * @param string|null $reason Cancellation reason
     * @return WorkflowResult Result after cancellation
     */
    public function cancel(string $workflowInstanceId, ?string $reason = null): WorkflowResult;

    /**
     * Get the current state of a workflow instance.
     *
     * @param string $workflowInstanceId The instance to query
     * @return WorkflowStateInterface|null Current state or null if not found
     */
    public function getState(string $workflowInstanceId): ?WorkflowStateInterface;

    /**
     * Check if workflow can be started with given context.
     *
     * @param WorkflowContext $context Context to validate
     * @return bool True if workflow can start
     */
    public function canStart(WorkflowContext $context): bool;

    /**
     * Get all steps in this workflow.
     *
     * @return array<WorkflowStepInterface>
     */
    public function getSteps(): array;
}
