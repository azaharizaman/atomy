<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\DTOs;

final readonly class DataOnboardingRequest
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $taskId,
        public string $tenantId,
        public string $sourcePath,
        public array $options = [],
    ) {}
}
