<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DTOs;

final readonly class SagaStepContext
{
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

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function getStepOutput(string $stepId, ?string $key = null): mixed
    {
        $stepData = $this->stepOutputs[$stepId] ?? [];

        if ($key === null) {
            return $stepData;
        }

        return $stepData[$key] ?? null;
    }

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
