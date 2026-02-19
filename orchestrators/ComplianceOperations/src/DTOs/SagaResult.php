<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DTOs;

use Nexus\ComplianceOperations\Enums\SagaStatus;

/**
 * Result of saga execution.
 */
final readonly class SagaResult
{
    /**
     * @param string $instanceId Saga instance identifier
     * @param string $sagaId Saga type identifier
     * @param SagaStatus $status Final status
     * @param array<string> $completedSteps Steps that completed successfully
     * @param array<string> $compensatedSteps Steps that were compensated
     * @param string|null $failedStep Step that failed (if any)
     * @param string|null $errorMessage Error message (if failed)
     * @param array<string, array<string, mixed>> $data Output data from steps
     * @param bool $compensationSucceeded Whether all compensations succeeded
     */
    public function __construct(
        public string $instanceId,
        public string $sagaId,
        public SagaStatus $status,
        public array $completedSteps = [],
        public array $compensatedSteps = [],
        public ?string $failedStep = null,
        public ?string $errorMessage = null,
        public array $data = [],
        public bool $compensationSucceeded = true,
    ) {}

    /**
     * Check if saga completed successfully.
     */
    public function isSuccessful(): bool
    {
        return $this->status === SagaStatus::COMPLETED;
    }

    /**
     * Check if saga was compensated.
     */
    public function wasCompensated(): bool
    {
        return $this->status === SagaStatus::COMPENSATED;
    }

    /**
     * Create a successful result.
     *
     * @param string $instanceId Saga instance ID
     * @param string $sagaId Saga type ID
     * @param array<string> $completedSteps Completed step IDs
     * @param array<string, array<string, mixed>> $data Output data
     */
    public static function success(
        string $instanceId,
        string $sagaId,
        array $completedSteps,
        array $data = [],
    ): self {
        return new self(
            instanceId: $instanceId,
            sagaId: $sagaId,
            status: SagaStatus::COMPLETED,
            completedSteps: $completedSteps,
            compensatedSteps: [],
            failedStep: null,
            errorMessage: null,
            data: $data,
            compensationSucceeded: true,
        );
    }

    /**
     * Create a failed result without compensation.
     *
     * @param string $instanceId Saga instance ID
     * @param string $sagaId Saga type ID
     * @param string $failedStep Step that failed
     * @param string $errorMessage Error message
     */
    public static function failed(
        string $instanceId,
        string $sagaId,
        string $failedStep,
        string $errorMessage,
    ): self {
        return new self(
            instanceId: $instanceId,
            sagaId: $sagaId,
            status: SagaStatus::FAILED,
            completedSteps: [],
            compensatedSteps: [],
            failedStep: $failedStep,
            errorMessage: $errorMessage,
            data: [],
            compensationSucceeded: false,
        );
    }

    /**
     * Create a failed result with compensation.
     *
     * @param string $instanceId Saga instance ID
     * @param string $sagaId Saga type ID
     * @param string $failedStep Step that failed
     * @param string $errorMessage Error message
     * @param array<string> $completedSteps Steps completed before failure
     * @param array<string> $compensatedSteps Steps that were compensated
     * @param bool $compensationSucceeded Whether all compensations succeeded
     */
    public static function failedWithCompensation(
        string $instanceId,
        string $sagaId,
        string $failedStep,
        string $errorMessage,
        array $completedSteps,
        array $compensatedSteps,
        bool $compensationSucceeded,
    ): self {
        return new self(
            instanceId: $instanceId,
            sagaId: $sagaId,
            status: $compensationSucceeded ? SagaStatus::COMPENSATED : SagaStatus::COMPENSATION_FAILED,
            completedSteps: $completedSteps,
            compensatedSteps: $compensatedSteps,
            failedStep: $failedStep,
            errorMessage: $errorMessage,
            data: [],
            compensationSucceeded: $compensationSucceeded,
        );
    }
}
