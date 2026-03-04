<?php

declare(strict_types=1);

namespace Nexus\Laravel\IntelligenceOperations\Adapters;

use Nexus\IntelligenceOperations\Contracts\ModelRegistryPortInterface;
use Nexus\MachineLearning\Contracts\ModelRepositoryInterface;

final readonly class ModelRegistryPortAdapter implements ModelRegistryPortInterface
{
    public function __construct(private ModelRepositoryInterface $repository) {}

    public function registerVersion(string $modelId, string $version, array $configuration): bool
    {
        $storedId = $this->repository->storeModelConfiguration(array_merge($configuration, [
            'model_name' => $modelId,
            'version' => $version,
            'active' => true,
            'deployed_at' => gmdate('Y-m-d H:i:s'),
        ]));

        return $storedId !== '';
    }

    public function getCurrentVersion(string $modelId): ?array
    {
        return $this->repository->getCurrentVersion($modelId);
    }

    public function getVersionHistory(string $modelId): array
    {
        return $this->repository->getVersionHistory($modelId);
    }
}
