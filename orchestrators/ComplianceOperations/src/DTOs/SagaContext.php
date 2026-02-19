<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DTOs;

/**
 * Context for saga execution.
 */
final readonly class SagaContext
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $userId User initiating the saga
     * @param array<string, mixed> $data Saga-specific data
     * @param array<string, mixed> $metadata Additional metadata
     * @param string|null $correlationId Correlation ID for tracing
     * @param string|null $sagaInstanceId Saga instance identifier
     * @param array<string, array<string, mixed>> $stepOutputs Outputs from completed steps
     */
    public function __construct(
        public string $tenantId,
        public string $userId,
        public array $data = [],
        public array $metadata = [],
        public ?string $correlationId = null,
        public ?string $sagaInstanceId = null,
        public array $stepOutputs = [],
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
     * Create new context with additional data.
     *
     * @param array<string, mixed> $data Data to merge
     */
    public function withData(array $data): self
    {
        return new self(
            tenantId: $this->tenantId,
            userId: $this->userId,
            data: array_merge($this->data, $data),
            metadata: $this->metadata,
            correlationId: $this->correlationId,
            sagaInstanceId: $this->sagaInstanceId,
            stepOutputs: $this->stepOutputs,
        );
    }

    /**
     * Create new context with saga instance ID.
     */
    public function withInstanceId(string $instanceId): self
    {
        return new self(
            tenantId: $this->tenantId,
            userId: $this->userId,
            data: $this->data,
            metadata: $this->metadata,
            correlationId: $this->correlationId,
            sagaInstanceId: $instanceId,
            stepOutputs: $this->stepOutputs,
        );
    }

    /**
     * Create new context with step output data.
     *
     * @param string $stepId Step that produced the data
     * @param array<string, mixed> $stepData Step output data
     */
    public function withStepOutput(string $stepId, array $stepData): self
    {
        $newOutputs = $this->stepOutputs;
        $newOutputs[$stepId] = $stepData;

        return new self(
            tenantId: $this->tenantId,
            userId: $this->userId,
            data: $this->data,
            metadata: $this->metadata,
            correlationId: $this->correlationId,
            sagaInstanceId: $this->sagaInstanceId,
            stepOutputs: $newOutputs,
        );
    }

    /**
     * Get output from a specific step.
     *
     * @param string $stepId Step identifier
     * @return array<string, mixed>|null Step output or null
     */
    public function getStepOutput(string $stepId): ?array
    {
        return $this->stepOutputs[$stepId] ?? null;
    }
}
