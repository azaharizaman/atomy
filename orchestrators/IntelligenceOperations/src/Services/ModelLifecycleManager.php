<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Services;

use Nexus\IntelligenceOperations\Coordinators\ModelLifecycleCoordinator;
use Nexus\IntelligenceOperations\Contracts\ModelLifecycleCoordinatorInterface;

/**
 * Compatibility facade retained for callers using src/Services namespace.
 */
final readonly class ModelLifecycleManager implements ModelLifecycleCoordinatorInterface
{
    public function __construct(private ModelLifecycleCoordinator $coordinator) {}

    public function deployModel(string $modelId, string $version, array $config = []): bool
    {
        return $this->coordinator->deployModel($modelId, $version, $config);
    }

    public function triggerRetraining(string $modelId): string
    {
        return $this->coordinator->triggerRetraining($modelId);
    }

    /**
     * @return array{accuracy: float, latency_ms: float, drift_score: float, status: string}
     */
    public function getModelHealth(string $modelId): array
    {
        return $this->coordinator->getModelHealth($modelId);
    }
}
