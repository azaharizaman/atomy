<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use DateTimeImmutable;
use Nexus\ProcurementOperations\Enums\WorkflowStatus;

/**
 * Represents the current state of a workflow instance.
 */
interface WorkflowStateInterface
{
    /**
     * Get the workflow instance ID.
     */
    public function getInstanceId(): string;

    /**
     * Get the workflow definition ID.
     */
    public function getWorkflowId(): string;

    /**
     * Get the current status.
     */
    public function getStatus(): WorkflowStatus;

    /**
     * Get the current step name/identifier.
     */
    public function getCurrentStep(): ?string;

    /**
     * Get the list of completed steps.
     *
     * @return array<string>
     */
    public function getCompletedSteps(): array;

    /**
     * Get the context data stored with the workflow.
     *
     * @return array<string, mixed>
     */
    public function getContextData(): array;

    /**
     * Get when the workflow was started.
     */
    public function getStartedAt(): DateTimeImmutable;

    /**
     * Get when the workflow was last updated.
     */
    public function getUpdatedAt(): DateTimeImmutable;

    /**
     * Get when the workflow was completed (if applicable).
     */
    public function getCompletedAt(): ?DateTimeImmutable;

    /**
     * Get any error message if workflow failed.
     */
    public function getErrorMessage(): ?string;

    /**
     * Get the tenant ID this workflow belongs to.
     */
    public function getTenantId(): string;

    /**
     * Check if workflow is in a terminal state.
     */
    public function isTerminal(): bool;

    /**
     * Check if workflow can be resumed.
     */
    public function canResume(): bool;

    /**
     * Check if workflow can be cancelled.
     */
    public function canCancel(): bool;
}
