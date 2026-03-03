<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Contracts;

interface ModelRegistryPortInterface
{
    /**
     * @param array<string, mixed> $configuration
     */
    public function registerVersion(string $modelId, string $version, array $configuration): bool;

    /**
     * @return array<string, mixed>|null
     */
    public function getCurrentVersion(string $modelId): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getVersionHistory(string $modelId): array;
}
