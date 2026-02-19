<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DTOs;

use Nexus\ComplianceOperations\Contracts\SagaStateInterface;
use Nexus\ComplianceOperations\Enums\SagaStatus;

/**
 * Immutable DTO representing saga state for persistence.
 */
final readonly class SagaState implements SagaStateInterface
{
    public function __construct(
        private string $instanceId,
        private string $sagaId,
        private string $tenantId,
        private SagaStatus $status,
        private array $completedSteps,
        private array $compensatedSteps,
        private array $contextData,
        private array $stepData,
        private ?string $errorMessage,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {}

    /**
     * Create a new saga state.
     *
     * @param string $instanceId Saga instance ID
     * @param string $sagaId Saga type ID
     * @param string $tenantId Tenant identifier
     * @param SagaStatus $status Current status
     * @param array<string> $completedSteps Completed step IDs
     * @param array<string> $compensatedSteps Compensated step IDs
     * @param array<string, mixed> $contextData Context data
     * @param array<string, array<string, mixed>> $stepData Step output data
     * @param string|null $errorMessage Error message if failed
     */
    public static function create(
        string $instanceId,
        string $sagaId,
        string $tenantId,
        SagaStatus $status,
        array $completedSteps = [],
        array $compensatedSteps = [],
        array $contextData = [],
        array $stepData = [],
        ?string $errorMessage = null,
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
            errorMessage: $errorMessage,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    /**
     * Create updated state with new status and step information.
     *
     * @param SagaStatus $status New status
     * @param array<string> $completedSteps Updated completed steps
     * @param array<string> $compensatedSteps Updated compensated steps
     * @param string|null $errorMessage Error message if any
     */
    public function withUpdatedStatus(
        SagaStatus $status,
        array $completedSteps,
        array $compensatedSteps,
        ?string $errorMessage = null,
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
            errorMessage: $errorMessage ?? $this->errorMessage,
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

    public function getStatus(): SagaStatus
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

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }
}
