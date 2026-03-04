<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\DTOs;

final readonly class ProviderCallRequest
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $providerId,
        public string $endpoint,
        public array $payload,
        public array $options,
    ) {}
}
