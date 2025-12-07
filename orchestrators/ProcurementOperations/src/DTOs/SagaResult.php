<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\ProcurementOperations\Enums\SagaStatus;

/**
 * Result of saga execution.
 */
final readonly class SagaResult
{
    /**
     * @param string $instanceId Saga instance identifier
     * @param string $sagaId Saga definition identifier
     * @param SagaStatus $status Current status
     * @param array<string> $completedSteps Steps that completed successfully
     * @param array<string> $compensatedSteps Steps that were compensated
     * @param array<string, mixed> $data Result data from steps
     * @param string|null $failedStep Step that caused failure (if any)
     * @param string|null $errorMessage Error message if failed
     */
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

    /**
     * Check if saga completed successfully.
     */
    public function isSuccessful(): bool
    {
        return $this->status === SagaStatus::COMPLETED;
    }

    /**
     * Check if saga failed.
     */
    public function isFailed(): bool
    {
        return in_array($this->status, [
            SagaStatus::FAILED,
            SagaStatus::COMPENSATION_FAILED,
        ], true);
    }

    /**
     * Check if compensation was triggered.
     */
    public function wasCompensated(): bool
    {
        return $this->status->wasCompensated();
    }

    /**
     * Check if compensation completed successfully.
     */
    public function isCompensationComplete(): bool
    {
        return $this->status === SagaStatus::COMPENSATED;
    }

    /**
     * Create a successful result.
     *
     * @param string $instanceId Saga instance ID
     * @param string $sagaId Saga definition ID
     * @param array<string> $completedSteps Completed steps
     * @param array<string, mixed> $data Result data
     */
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

    /**
     * Create a failed result with compensation.
     *
     * @param string $instanceId Saga instance ID
     * @param string $sagaId Saga definition ID
     * @param string $failedStep Step that failed
     * @param string $errorMessage Error message
     * @param array<string> $completedSteps Steps completed before failure
     * @param array<string> $compensatedSteps Steps that were compensated
     * @param bool $compensationSucceeded Whether compensation completed
     */
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

    /**
     * Create a failed result without compensation attempt.
     *
     * @param string $instanceId Saga instance ID
     * @param string $sagaId Saga definition ID
     * @param string $failedStep Step that failed
     * @param string $errorMessage Error message
     * @param array<string> $completedSteps Steps completed before failure
     */
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

    /**
     * Create a result with explicit status (for flexibility).
     *
     * @param SagaStatus $status Saga status
     * @param array<string> $completedSteps Completed steps
     * @param array<string> $compensatedSteps Compensated steps
     * @param string|null $failedStep Failed step
     * @param string|null $errorMessage Error message
     * @param array<string, mixed> $data Result data
     */
    public static function withStatus(
        SagaStatus $status,
        array $completedSteps = [],
        array $compensatedSteps = [],
        ?string $failedStep = null,
        ?string $errorMessage = null,
        array $data = [],
    ): self {
        return new self(
            instanceId: '',  // Will be set by workflow
            sagaId: '',      // Will be set by workflow
            status: $status,
            completedSteps: $completedSteps,
            compensatedSteps: $compensatedSteps,
            data: $data,
            failedStep: $failedStep,
            errorMessage: $errorMessage,
        );
    }
}
