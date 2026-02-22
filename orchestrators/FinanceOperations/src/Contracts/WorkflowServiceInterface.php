<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Interface for workflow services.
 *
 * Defines the contract for creating and managing workflows
 * within the FinanceOperations orchestrator.
 */
interface WorkflowServiceInterface
{
    /**
     * Create an approval workflow.
     *
     * @param array<string, mixed> $data Workflow data containing:
     *                                    - type: string
     *                                    - budget_id: string
     *                                    - severity: string
     * @return void
     */
    public function createApprovalWorkflow(array $data): void;
}
