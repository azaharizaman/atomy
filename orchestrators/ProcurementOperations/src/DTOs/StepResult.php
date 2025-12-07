<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Result of workflow step execution.
 */
final readonly class StepResult
{
    /**
     * @param bool $success Whether step succeeded
     * @param array<string, mixed> $data Step output data
     * @param string|null $errorMessage Error message if failed
     * @param bool $shouldContinue Whether workflow should continue to next step
     * @param string|null $nextStep Override next step (null = use default)
     */
    public function __construct(
        public bool $success,
        public array $data = [],
        public ?string $errorMessage = null,
        public bool $shouldContinue = true,
        public ?string $nextStep = null,
    ) {}

    /**
     * Create a successful result.
     *
     * @param array<string, mixed> $data Step output data
     * @param string|null $nextStep Override next step
     */
    public static function success(array $data = [], ?string $nextStep = null): self
    {
        return new self(
            success: true,
            data: $data,
            errorMessage: null,
            shouldContinue: true,
            nextStep: $nextStep,
        );
    }

    /**
     * Create a failed result.
     *
     * @param string $errorMessage Error message
     * @param bool $shouldContinue Whether to continue with next step
     */
    public static function failure(string $errorMessage, bool $shouldContinue = false): self
    {
        return new self(
            success: false,
            data: [],
            errorMessage: $errorMessage,
            shouldContinue: $shouldContinue,
            nextStep: null,
        );
    }

    /**
     * Create a result indicating workflow should pause.
     *
     * @param array<string, mixed> $data Current data
     * @param string $reason Reason for pausing
     */
    public static function pause(array $data = [], string $reason = 'Waiting for external input'): self
    {
        return new self(
            success: true,
            data: $data,
            errorMessage: $reason,
            shouldContinue: false,
            nextStep: null,
        );
    }

    /**
     * Create a result indicating step was skipped.
     *
     * @param string $reason Reason for skipping
     */
    public static function skipped(string $reason = 'Step conditions not met'): self
    {
        return new self(
            success: true,
            data: ['skipped' => true, 'reason' => $reason],
            errorMessage: null,
            shouldContinue: true,
            nextStep: null,
        );
    }
}
