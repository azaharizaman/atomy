<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Workflows;

use Nexus\IntelligenceOperations\DataProviders\ModelHealthDataProvider;
use Nexus\IntelligenceOperations\DTOs\ModelHealthSnapshot;
use Nexus\IntelligenceOperations\Rules\ModelHealthRule;

final readonly class ModelHealthWorkflow
{
    public function __construct(
        private ModelHealthDataProvider $healthDataProvider,
        private ModelHealthRule $rule,
    ) {}

    public function run(string $modelId): ModelHealthSnapshot
    {
        if ($modelId === '') {
            throw new \InvalidArgumentException('modelId is required for health checks.');
        }

        $data = $this->healthDataProvider->fetch($modelId);

        return new ModelHealthSnapshot(
            accuracy: $data['accuracy'],
            latencyMs: $data['latency_ms'],
            driftScore: $data['drift_score'],
            status: $this->rule->status($data['accuracy'], $data['latency_ms'], $data['drift_score']),
        );
    }
}
