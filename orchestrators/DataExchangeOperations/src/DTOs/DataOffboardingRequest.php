<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\DTOs;

final readonly class DataOffboardingRequest
{
    /**
     * @param array<string, mixed> $query
     * @param array<int, string> $recipients
     */
    public function __construct(
        public string $taskId,
        public string $tenantId,
        public array $query,
        public string $format,
        public string $destination,
        public array $recipients = [],
    ) {}
}
