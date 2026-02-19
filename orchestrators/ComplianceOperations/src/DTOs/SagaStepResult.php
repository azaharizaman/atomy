<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DTOs;

/**
 * Result of saga step execution or compensation.
 */
final readonly class SagaStepResult
{
    /**
     * @param bool $success Whether step succeeded
     * @param array<string, mixed> $data Step output data
     * @param string|null $errorMessage Error message if failed
     * @param bool $shouldCompensate Whether compensation should be triggered
     * @param bool $canRetry Whether step can be retried
     */
    public function __construct(
        public bool $success,
        public array $data = [],
        public ?string $errorMessage = null,
        public bool $shouldCompensate = false,
        public bool $canRetry = false,
    ) {}

    /**
     * Check if step was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Create a successful result.
     *
     * @param array<string, mixed> $data Step output data
     */
    public static function success(array $data = []): self
    {
        return new self(
            success: true,
            data: $data,
            errorMessage: null,
            shouldCompensate: false,
            canRetry: false,
        );
    }

    /**
     * Create a failed result that triggers compensation.
     *
     * @param string $errorMessage Error message
     * @param bool $canRetry Whether step can be retried before compensating
     */
    public static function failure(string $errorMessage, bool $canRetry = false): self
    {
        return new self(
            success: false,
            data: [],
            errorMessage: $errorMessage,
            shouldCompensate: true,
            canRetry: $canRetry,
        );
    }

    /**
     * Create a failed result that doesn't trigger compensation.
     *
     * Used for non-critical failures that shouldn't roll back previous steps.
     *
     * @param string $errorMessage Error message
     */
    public static function nonCriticalFailure(string $errorMessage): self
    {
        return new self(
            success: false,
            data: [],
            errorMessage: $errorMessage,
            shouldCompensate: false,
            canRetry: true,
        );
    }

    /**
     * Create a successful compensation result.
     *
     * @param array<string, mixed> $data Compensation data
     */
    public static function compensated(array $data = []): self
    {
        return new self(
            success: true,
            data: $data,
            errorMessage: null,
            shouldCompensate: false,
            canRetry: false,
        );
    }

    /**
     * Create a failed compensation result.
     *
     * @param string $errorMessage Why compensation failed
     */
    public static function compensationFailed(string $errorMessage): self
    {
        return new self(
            success: false,
            data: [],
            errorMessage: $errorMessage,
            shouldCompensate: false,
            canRetry: true,
        );
    }
}
