<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result of a workflow step execution.
 *
 * Represents the outcome of executing a single step within a workflow,
 * including success/failure status and whether compensation is needed.
 *
 * @since 1.0.0
 */
final readonly class WorkflowStepResult
{
    /**
     * @param bool $success Whether the step completed successfully
     * @param string $stepName Name of the executed step
     * @param array<string, mixed> $data Output data from the step
     * @param string|null $error Error message if step failed
     * @param bool $requiresCompensation Whether compensation should be triggered on failure
     */
    public function __construct(
        public bool $success,
        public string $stepName,
        public array $data = [],
        public ?string $error = null,
        public bool $requiresCompensation = true,
    ) {}

    /**
     * Check if the step was successful.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Create a successful step result.
     *
     * @param string $stepName Name of the step
     * @param array<string, mixed> $data Output data
     * @return self
     */
    public static function success(string $stepName, array $data = []): self
    {
        return new self(
            success: true,
            stepName: $stepName,
            data: $data,
            error: null,
            requiresCompensation: false,
        );
    }

    /**
     * Create a failed step result.
     *
     * @param string $stepName Name of the step
     * @param string $error Error message
     * @param bool $requiresCompensation Whether compensation is needed
     * @return self
     */
    public static function failure(string $stepName, string $error, bool $requiresCompensation = true): self
    {
        return new self(
            success: false,
            stepName: $stepName,
            data: [],
            error: $error,
            requiresCompensation: $requiresCompensation,
        );
    }
}
