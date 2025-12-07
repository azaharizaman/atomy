<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Context for individual saga step execution.
 */
final readonly class SagaStepContext
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $userId User running the saga
     * @param string $sagaInstanceId Parent saga instance ID
     * @param string $stepId Current step identifier
     * @param array<string, mixed> $data Step-specific data
     * @param array<string, array<string, mixed>> $stepOutputs Outputs from previous steps
     * @param array<string, mixed> $metadata Additional metadata
     * @param bool $isCompensation Whether this is a compensation execution
     */
    public function __construct(
        public string $tenantId,
        public string $userId,
        public string $sagaInstanceId,
        public string $stepId,
        public array $data = [],
        public array $stepOutputs = [],
        public array $metadata = [],
        public bool $isCompensation = false,
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
     * Get output from a specific step.
     *
     * @param string $stepId Step identifier
     * @param string|null $key Specific key to retrieve
     * @return mixed Step output or specific value
     */
    public function getStepOutput(string $stepId, ?string $key = null): mixed
    {
        $stepData = $this->stepOutputs[$stepId] ?? [];

        if ($key === null) {
            return $stepData;
        }

        return $stepData[$key] ?? null;
    }

    /**
     * Create from saga context.
     *
     * @param SagaContext $sagaContext Parent saga context
     * @param string $sagaInstanceId Saga instance ID
     * @param string $stepId Step identifier
     * @param bool $isCompensation Whether this is compensation
     */
    public static function fromSagaContext(
        SagaContext $sagaContext,
        string $sagaInstanceId,
        string $stepId,
        bool $isCompensation = false,
    ): self {
        return new self(
            tenantId: $sagaContext->tenantId,
            userId: $sagaContext->userId,
            sagaInstanceId: $sagaInstanceId,
            stepId: $stepId,
            data: $sagaContext->data,
            stepOutputs: $sagaContext->data['step_outputs'] ?? [],
            metadata: $sagaContext->metadata,
            isCompensation: $isCompensation,
        );
    }

    /**
     * Create a compensation context from this context.
     *
     * @param array<string, array<string, mixed>>|null $stepOutputs Updated step outputs
     */
    public function forCompensation(?array $stepOutputs = null): self
    {
        return new self(
            tenantId: $this->tenantId,
            userId: $this->userId,
            sagaInstanceId: $this->sagaInstanceId,
            stepId: $this->stepId,
            data: $this->data,
            stepOutputs: $stepOutputs ?? $this->stepOutputs,
            metadata: $this->metadata,
            isCompensation: true,
        );
    }
}
