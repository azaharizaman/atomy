<?php

declare(strict_types=1);

namespace Nexus\Laravel\IntelligenceOperations\Adapters;

use Illuminate\Support\Str;
use Nexus\IntelligenceOperations\Contracts\ModelTrainingPortInterface;
use Nexus\MachineLearning\Contracts\ModelRepositoryInterface;

final readonly class ModelTrainingPortAdapter implements ModelTrainingPortInterface
{
    public function __construct(private ModelRepositoryInterface $repository) {}

    public function queueRetraining(string $modelId, array $context): string
    {
        $jobId = sprintf('retrain_%s', (string) Str::uuid());

        $this->repository->recordUsage(
            tenantId: (string) ($context['tenant_id'] ?? 'system'),
            modelName: $modelId,
            domainContext: 'retraining',
            metrics: [
                'job_id' => $jobId,
                'trigger' => $context['trigger'] ?? 'scheduled',
                'drift_score' => (float) ($context['drift_score'] ?? 0.0),
                'queued_at' => $context['queued_at'] ?? gmdate('Y-m-d H:i:s'),
            ]
        );

        return $jobId;
    }
}
