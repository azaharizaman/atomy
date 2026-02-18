<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DTOs;

use Nexus\SalesOperations\Enums\SagaStatus;

final readonly class SagaResult
{
    public function __construct(
        public string $instanceId,
        public string $sagaId,
        public SagaStatus $status,
        public array $completedSteps = [],
        public array $compensatedSteps = [],
        public array $data = [],
        public ?string $failedStep = null,
        public ?string $errorMessage = null,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->status === SagaStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return in_array($this->status, [
            SagaStatus::FAILED,
            SagaStatus::COMPENSATION_FAILED,
        ], true);
    }

    public function wasCompensated(): bool
    {
        return $this->status->wasCompensated();
    }

    public function isCompensationComplete(): bool
    {
        return $this->status === SagaStatus::COMPENSATED;
    }

    public static function success(
        string $instanceId,
        string $sagaId,
        array $completedSteps = [],
        array $data = [],
    ): self {
        return new self(
            instanceId: $instanceId,
            sagaId: $sagaId,
            status: SagaStatus::COMPLETED,
            completedSteps: $completedSteps,
            compensatedSteps: [],
            data: $data,
            failedStep: null,
            errorMessage: null,
        );
    }

    public static function failedWithCompensation(
        string $instanceId,
        string $sagaId,
        string $failedStep,
        string $errorMessage,
        array $completedSteps = [],
        array $compensatedSteps = [],
        bool $compensationSucceeded = true,
    ): self {
        return new self(
            instanceId: $instanceId,
            sagaId: $sagaId,
            status: $compensationSucceeded ? SagaStatus::COMPENSATED : SagaStatus::COMPENSATION_FAILED,
            completedSteps: $completedSteps,
            compensatedSteps: $compensatedSteps,
            data: [],
            failedStep: $failedStep,
            errorMessage: $errorMessage,
        );
    }

    public static function failed(
        string $instanceId,
        string $sagaId,
        string $failedStep,
        string $errorMessage,
        array $completedSteps = [],
    ): self {
        return new self(
            instanceId: $instanceId,
            sagaId: $sagaId,
            status: SagaStatus::FAILED,
            completedSteps: $completedSteps,
            compensatedSteps: [],
            data: [],
            failedStep: $failedStep,
            errorMessage: $errorMessage,
        );
    }
}
