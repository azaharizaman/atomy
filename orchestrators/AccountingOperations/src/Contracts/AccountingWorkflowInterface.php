<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Contracts;

use Nexus\AccountingOperations\DTOs\PeriodCloseRequest;
use Nexus\AccountingOperations\DTOs\StatementGenerationRequest;
use Nexus\AccountingOperations\DTOs\ConsolidationRequest;

/**
 * Contract for accounting workflow execution.
 */
interface AccountingWorkflowInterface
{
    /**
     * Get the workflow name.
     */
    public function getName(): string;

    /**
     * Check if the workflow can be started.
     */
    public function canStart(array $context): bool;

    /**
     * Execute the workflow.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function execute(array $context): array;

    /**
     * Get the current workflow step.
     */
    public function getCurrentStep(): ?string;

    /**
     * Check if the workflow is complete.
     */
    public function isComplete(): bool;

    /**
     * Get workflow execution logs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getExecutionLog(): array;
}
