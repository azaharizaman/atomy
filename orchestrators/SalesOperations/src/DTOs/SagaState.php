<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DTOs;

use Nexus\SalesOperations\Contracts\SagaStateInterface;

final readonly class SagaState implements SagaStateInterface
{
    public function __construct(
        private string $instanceId,
        private string $sagaId,
        private string $tenantId,
        private string $status,
        private array $completedSteps,
        private array $compensatedSteps,
        private array $contextData,
        private array $stepData,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        string $instanceId,
        string $sagaId,
        string $tenantId,
        string $status,
        array $completedSteps = [],
        array $compensatedSteps = [],
        array $contextData = [],
        array $stepData = [],
    ): self {
        $now = new \DateTimeImmutable();

        return new self(
            instanceId: $instanceId,
            sagaId: $sagaId,
            tenantId: $tenantId,
            status: $status,
            completedSteps: $completedSteps,
            compensatedSteps: $compensatedSteps,
            contextData: $contextData,
            stepData: $stepData,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function withUpdatedStatus(
        string $status,
        array $completedSteps,
        array $compensatedSteps,
    ): self {
        return new self(
            instanceId: $this->instanceId,
            sagaId: $this->sagaId,
            tenantId: $this->tenantId,
            status: $status,
            completedSteps: $completedSteps,
            compensatedSteps: $compensatedSteps,
            contextData: $this->contextData,
            stepData: $this->stepData,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function getInstanceId(): string
    {
        return $this->instanceId;
    }

    public function getSagaId(): string
    {
        return $this->sagaId;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCompletedSteps(): array
    {
        return $this->completedSteps;
    }

    public function getCompensatedSteps(): array
    {
        return $this->compensatedSteps;
    }

    public function getContextData(): array
    {
        return $this->contextData;
    }

    public function getStepData(): array
    {
        return $this->stepData;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}