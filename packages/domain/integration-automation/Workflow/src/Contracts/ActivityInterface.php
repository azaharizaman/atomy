<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Contract for workflow activity plugins.
 *
 * Activities are executable actions (send email, call API, update database, etc.)
 * that can be attached to states via onEntry/onExit hooks.
 */
interface ActivityInterface
{
    /**
     * Get the activity name.
     */
    public function getName(): string;

    /**
     * Execute the activity.
     *
     * @param WorkflowInterface $workflow The workflow instance
     * @param array<string, mixed> $config Activity configuration
     * @return array<string, mixed> Execution result
     * @throws \Exception On execution failure
     */
    public function execute(WorkflowInterface $workflow, array $config = []): array;

    /**
     * Execute compensation/rollback logic.
     *
     * Called when workflow needs to be rolled back after this activity succeeded.
     *
     * @param WorkflowInterface $workflow The workflow instance
     * @param array<string, mixed> $config Activity configuration
     * @param array<string, mixed> $executionResult Result from original execute() call
     */
    public function compensate(WorkflowInterface $workflow, array $config = [], array $executionResult = []): void;

    /**
     * Get the activity schema definition.
     *
     * Defines expected configuration parameters.
     *
     * @return array<string, mixed>
     */
    public function getSchema(): array;
}
