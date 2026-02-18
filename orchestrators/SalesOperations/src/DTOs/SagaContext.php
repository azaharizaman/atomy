<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DTOs;

final readonly class SagaContext
{
    public function __construct(
        public string $tenantId,
        public string $userId,
        public array $data = [],
        public array $metadata = [],
        public ?string $correlationId = null,
        public ?string $sagaInstanceId = null,
        public array $stepOutputs = [],
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

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

    public function getStepOutput(string $stepId): ?array
    {
        return $this->stepOutputs[$stepId] ?? null;
    }
}
