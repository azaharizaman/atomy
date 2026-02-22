<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Contract for finance workflow execution following the Saga pattern.
 *
 * This interface defines the contract for long-running finance workflows
 * that require compensation logic for rollback scenarios.
 *
 * Interface Segregation Compliance:
 * - Defines workflow-specific operations with compensation support
 * - Follows the Saga pattern for distributed transactions
 * - Used by coordinators to manage multi-step processes
 *
 * @see WorkflowStepInterface
 * @see \Nexus\FinanceOperations\DTOs\WorkflowResult
 */
interface FinanceWorkflowInterface
{
    /**
     * Get the workflow name for identification and logging.
     */
    public function getName(): string;

    /**
     * Check if the workflow can be started with given context.
     *
     * @param array<string, mixed> $context The workflow context
     * @return bool True if workflow can be started
     */
    public function canStart(array $context): bool;

    /**
     * Execute the workflow with given context.
     *
     * @param array<string, mixed> $context The workflow context
     * @return \Nexus\FinanceOperations\DTOs\WorkflowResult The workflow result
     */
    public function execute(array $context): \Nexus\FinanceOperations\DTOs\WorkflowResult;

    /**
     * Compensate (rollback) a completed workflow.
     *
     * @param \Nexus\FinanceOperations\DTOs\WorkflowResult $result The result to compensate
     */
    public function compensate(\Nexus\FinanceOperations\DTOs\WorkflowResult $result): void;

    /**
     * Get the current workflow step name.
     */
    public function getCurrentStep(): ?string;

    /**
     * Check if the workflow can be retried after failure.
     */
    public function canRetry(): bool;

    /**
     * Check if the workflow is complete.
     */
    public function isComplete(): bool;

    /**
     * Get workflow execution logs.
     *
     * @return array<int, array<string, mixed>> Execution log entries
     */
    public function getExecutionLog(): array;
}
