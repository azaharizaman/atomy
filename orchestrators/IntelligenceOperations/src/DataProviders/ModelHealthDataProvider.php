<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\DataProviders;

use Nexus\IntelligenceOperations\Contracts\DataDriftPortInterface;
use Nexus\IntelligenceOperations\Contracts\ModelTelemetryPortInterface;

final readonly class ModelHealthDataProvider
{
    public function __construct(
        private ModelTelemetryPortInterface $telemetryPort,
        private DataDriftPortInterface $driftPort,
    ) {}

    /**
     * @return array{accuracy:float,latency_ms:float,drift_score:float}
     */
    public function fetch(string $modelId): array
    {
        $metrics = $this->telemetryPort->modelMetrics($modelId);

        return [
            'accuracy' => (float) ($metrics['accuracy'] ?? 0.0),
            'latency_ms' => (float) ($metrics['latency_ms'] ?? 0.0),
            'drift_score' => $this->driftPort->calculateDriftScore($modelId),
        ];
    }
}
