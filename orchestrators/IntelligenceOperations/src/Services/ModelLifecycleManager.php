<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Services;

use Nexus\IntelligenceOperations\Contracts\ModelLifecycleCoordinatorInterface;

/**
 * Compatibility facade retained for callers using src/Services namespace.
 */
final readonly class ModelLifecycleManager implements ModelLifecycleCoordinatorInterface
{
    public function __construct(private \Nexus\IntelligenceOperations\Coordinators\ModelLifecycleCoordinator $coordinator) {}

    public function deployModel(string $modelId, string $version, array $config = []): bool
    {
        return $this->coordinator->deployModel($modelId, $version, $config);
    }

    public function triggerRetraining(string $modelId): string
    {
        return $this->coordinator->triggerRetraining($modelId);
    }

    public function getModelHealth(string $modelId): array
    {
        return $this->coordinator->getModelHealth($modelId);
    }
}
