<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\DTOs;

final readonly class ModelDeploymentRequest
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        public string $modelId,
        public string $version,
        public array $config,
    ) {}
}
