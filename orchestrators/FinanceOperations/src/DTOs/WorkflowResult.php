<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result of a workflow execution.
 *
 * Represents the outcome of a multi-step workflow process,
 * including success/failure status and step-by-step results.
 *
 * @since 1.0.0
 */
final readonly class WorkflowResult
{
    /**
     * @param bool $success Whether the workflow completed successfully
     * @param string $workflowId Unique identifier for the workflow execution
     * @param array<string, mixed> $data Output data from the workflow
     * @param string|null $currentStep Current step name (for in-progress workflows)
     * @param array<string> $errors List of error messages
     * @param array<string, array<string, mixed>> $stepResults Results from each step
     */
    public function __construct(
        public bool $success,
        public string $workflowId,
        public array $data = [],
        public ?string $currentStep = null,
        public array $errors = [],
        public array $stepResults = [],
    ) {}

    /**
     * Create a successful workflow result.
     *
     * @param string $workflowId Workflow identifier
     * @param array<string, mixed> $data Output data
     * @return self
     */
    public static function success(string $workflowId, array $data = []): self
    {
        return new self(
            success: true,
            workflowId: $workflowId,
            data: $data,
            currentStep: null,
            errors: [],
            stepResults: [],
        );
    }

    /**
     * Create a failed workflow result.
     *
     * @param string $workflowId Workflow identifier
     * @param array<string> $errors Error messages
     * @return self
     */
    public static function failure(string $workflowId, array $errors = []): self
    {
        return new self(
            success: false,
            workflowId: $workflowId,
            data: [],
            currentStep: null,
            errors: $errors,
            stepResults: [],
        );
    }

    /**
     * Check if the workflow is still in progress.
     *
     * @return bool
     */
    public function isInProgress(): bool
    {
        return $this->currentStep !== null && $this->success === false && empty($this->errors);
    }
}
