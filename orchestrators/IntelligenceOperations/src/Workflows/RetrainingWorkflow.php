<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Workflows;

use Nexus\IntelligenceOperations\Contracts\DataDriftPortInterface;
use Nexus\IntelligenceOperations\Contracts\ModelTelemetryPortInterface;
use Nexus\IntelligenceOperations\Contracts\ModelTrainingPortInterface;

final readonly class RetrainingWorkflow
{
    private const DRIFT_THRESHOLD = 0.20;

    public function __construct(
        private DataDriftPortInterface $driftPort,
        private ModelTrainingPortInterface $trainingPort,
        private ModelTelemetryPortInterface $telemetryPort,
    ) {}

    public function run(string $modelId): string
    {
        $modelId = trim($modelId);
        if ($modelId === '') {
            throw new \InvalidArgumentException('modelId is required for retraining.');
        }

        $driftScore = $this->driftPort->calculateDriftScore($modelId);
        if (is_nan($driftScore) || is_infinite($driftScore) || $driftScore < 0.0) {
            $driftScore = 0.0;
        }

        $modelIdBucket = 'b' . (string) (abs(crc32($modelId)) % 100);

        $jobId = $this->trainingPort->queueRetraining($modelId, [
            'trigger' => $driftScore >= self::DRIFT_THRESHOLD ? 'drift' : 'scheduled',
            'drift_score' => $driftScore,
            'queued_at' => gmdate(DATE_ATOM),
        ]);

        $this->telemetryPort->increment('intelligence.model.retraining.total', 1.0, ['model_id_bucket' => $modelIdBucket]);
        $this->telemetryPort->gauge('intelligence.model.drift_score', $driftScore, ['model_id_bucket' => $modelIdBucket]);

        return $jobId;
    }
}
