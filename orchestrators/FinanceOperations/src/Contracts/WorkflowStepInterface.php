<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Contract for individual workflow steps with compensation support.
 *
 * This interface defines the contract for workflow steps that can be
 * executed and compensated (rolled back) as part of a Saga pattern.
 *
 * Interface Segregation Compliance:
 * - Defines step-specific operations with compensation support
 * - Each step is self-contained with execute and compensate actions
 * - Used by workflows to compose multi-step processes
 *
 * @see FinanceWorkflowInterface
 * @see \Nexus\FinanceOperations\DTOs\WorkflowStepContext
 * @see \Nexus\FinanceOperations\DTOs\WorkflowStepResult
 */
interface WorkflowStepInterface
{
    /**
     * Get the step name for identification and logging.
     */
    public function getName(): string;

    /**
     * Execute the forward action of this step.
     *
     * @param \Nexus\FinanceOperations\DTOs\WorkflowStepContext $context The step context
     * @return \Nexus\FinanceOperations\DTOs\WorkflowStepResult The step result
     */
    public function execute(\Nexus\FinanceOperations\DTOs\WorkflowStepContext $context): \Nexus\FinanceOperations\DTOs\WorkflowStepResult;

    /**
     * Execute the compensation (rollback) action of this step.
     *
     * Called when a later step fails to undo this step's effects.
     *
     * @param \Nexus\FinanceOperations\DTOs\WorkflowStepContext $context The step context
     * @return \Nexus\FinanceOperations\DTOs\WorkflowStepResult The compensation result
     */
    public function compensate(\Nexus\FinanceOperations\DTOs\WorkflowStepContext $context): \Nexus\FinanceOperations\DTOs\WorkflowStepResult;
}
