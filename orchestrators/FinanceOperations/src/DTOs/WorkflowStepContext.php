<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Context for workflow step execution.
 *
 * Provides the execution context for a single step within a workflow,
 * including tenant isolation, workflow identification, and data from
 * previous steps.
 *
 * @since 1.0.0
 */
final readonly class WorkflowStepContext
{
    /**
     * @param string $tenantId Tenant identifier for multi-tenancy
     * @param string $workflowId Unique workflow execution identifier
     * @param string $stepName Name of the current step
     * @param array<string, mixed> $data Input data for the step
     * @param array<string, array<string, mixed>> $previousStepResults Results from completed steps
     */
    public function __construct(
        public string $tenantId,
        public string $workflowId,
        public string $stepName,
        public array $data = [],
        public array $previousStepResults = [],
    ) {}

    /**
     * Get a specific data value.
     *
     * @param string $key Data key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if a data key exists.
     *
     * @param string $key Data key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Create new context with additional data.
     *
     * @param array<string, mixed> $data Data to merge
     * @return self
     */
    public function withData(array $data): self
    {
        return new self(
            tenantId: $this->tenantId,
            workflowId: $this->workflowId,
            stepName: $this->stepName,
            data: array_merge($this->data, $data),
            previousStepResults: $this->previousStepResults,
        );
    }

    /**
     * Get result from a previous step.
     *
     * @param string $stepName Step name to get results from
     * @return array<string, mixed>|null Step results or null if not found
     */
    public function getPreviousStepResult(string $stepName): ?array
    {
        return $this->previousStepResults[$stepName] ?? null;
    }
}
