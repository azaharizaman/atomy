<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DTOs;

final readonly class SagaStepResult
{
    public function __construct(
        public bool $success,
        public array $data = [],
        public ?string $errorMessage = null,
        public bool $shouldCompensate = false,
        public bool $canRetry = false,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->success;
    }

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
