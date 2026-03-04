<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

final readonly class JobResult
{
    /** @param array<string, mixed> $data */
    private function __construct(
        public bool $successful,
        public ?string $error = null,
        public bool $shouldRetry = false,
        public array $data = []
    ) {}

    /** @param array<string, mixed> $data */
    public static function success(array $data = []): self
    {
        return new self(true, null, false, $data);
    }

    public static function failure(string $error, bool $shouldRetry = false): self
    {
        return new self(false, $error, $shouldRetry, []);
    }
}
