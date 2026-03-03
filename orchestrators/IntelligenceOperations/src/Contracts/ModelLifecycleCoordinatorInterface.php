<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Contracts;

interface ModelLifecycleCoordinatorInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function deployModel(string $modelId, string $version, array $config = []): bool;

    public function triggerRetraining(string $modelId): string;

    /**
     * @return array<string, mixed>
     */
    public function getModelHealth(string $modelId): array;
}
