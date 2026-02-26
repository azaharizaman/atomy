<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Contracts;

/**
 * Interface ModelLifecycleCoordinatorInterface
 *
 * Coordinates the lifecycle of AI/ML models across the enterprise.
 */
interface ModelLifecycleCoordinatorInterface
{
    /**
     * Deploys a new model version.
     *
     * @param string $modelId
     * @param string $version
     * @param array $config
     * @return bool
     */
    public function deployModel(string $modelId, string $version, array $config = []): bool;

    /**
     * Triggers model retraining based on data drift or schedule.
     *
     * @param string $modelId
     * @return string The training job identifier.
     */
    public function triggerRetraining(string $modelId): string;

    /**
     * Monitors model performance and cost.
     *
     * @param string $modelId
     * @return array Performance metrics.
     */
    public function getModelHealth(string $modelId): array;
}
