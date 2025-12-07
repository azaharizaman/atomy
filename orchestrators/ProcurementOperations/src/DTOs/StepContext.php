<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Context for individual workflow step execution.
 */
final readonly class StepContext
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $userId User running the workflow
     * @param string $workflowInstanceId Parent workflow instance ID
     * @param string $stepId Current step identifier
     * @param array<string, mixed> $data Step-specific data
     * @param array<string, mixed> $previousStepData Data from previous steps
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $tenantId,
        public string $userId,
        public string $workflowInstanceId,
        public string $stepId,
        public array $data = [],
        public array $previousStepData = [],
        public array $metadata = [],
    ) {}

    /**
     * Get a specific data value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if a data key exists.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get data from a previous step.
     *
     * @param string $stepId Previous step ID
     * @param string|null $key Specific key to retrieve
     * @return mixed Step data or specific value
     */
    public function getPreviousStepData(string $stepId, ?string $key = null): mixed
    {
        $stepData = $this->previousStepData[$stepId] ?? [];

        if ($key === null) {
            return $stepData;
        }

        return $stepData[$key] ?? null;
    }

    /**
     * Create from workflow context.
     *
     * @param WorkflowContext $workflowContext Parent workflow context
     * @param string $workflowInstanceId Workflow instance ID
     * @param string $stepId Step identifier
     * @param array<string, mixed> $previousStepData Data from previous steps
     */
    public static function fromWorkflowContext(
        WorkflowContext $workflowContext,
        string $workflowInstanceId,
        string $stepId,
        array $previousStepData = [],
    ): self {
        return new self(
            tenantId: $workflowContext->tenantId,
            userId: $workflowContext->userId,
            workflowInstanceId: $workflowInstanceId,
            stepId: $stepId,
            data: $workflowContext->data,
            previousStepData: $previousStepData,
            metadata: $workflowContext->metadata,
        );
    }
}
