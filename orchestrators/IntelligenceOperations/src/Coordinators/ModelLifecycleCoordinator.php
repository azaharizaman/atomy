<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Coordinators;

use Nexus\IntelligenceOperations\Contracts\ModelLifecycleCoordinatorInterface;
use Nexus\IntelligenceOperations\DTOs\ModelDeploymentRequest;
use Nexus\IntelligenceOperations\Workflows\ModelDeploymentWorkflow;
use Nexus\IntelligenceOperations\Workflows\ModelHealthWorkflow;
use Nexus\IntelligenceOperations\Workflows\RetrainingWorkflow;
use Psr\Log\LoggerInterface;

final readonly class ModelLifecycleCoordinator implements ModelLifecycleCoordinatorInterface
{
    public function __construct(
        private ModelDeploymentWorkflow $deploymentWorkflow,
        private RetrainingWorkflow $retrainingWorkflow,
        private ModelHealthWorkflow $healthWorkflow,
        private LoggerInterface $logger,
    ) {}

    public function deployModel(string $modelId, string $version, array $config = []): bool
    {
        $success = $this->deploymentWorkflow->run(new ModelDeploymentRequest($modelId, $version, $config));

        $this->logger->info('Model deployment executed.', [
            'model_id' => $modelId,
            'version' => $version,
            'success' => $success,
        ]);

        return $success;
    }

    public function triggerRetraining(string $modelId): string
    {
        $jobId = $this->retrainingWorkflow->run($modelId);

        $this->logger->info('Model retraining queued.', [
            'model_id' => $modelId,
            'job_id' => $jobId,
        ]);

        return $jobId;
    }

    public function getModelHealth(string $modelId): array
    {
        $snapshot = $this->healthWorkflow->run($modelId);

        $this->logger->info('Model health evaluated.', [
            'model_id' => $modelId,
            'status' => $snapshot->status,
        ]);

        return $snapshot->toArray();
    }
}
