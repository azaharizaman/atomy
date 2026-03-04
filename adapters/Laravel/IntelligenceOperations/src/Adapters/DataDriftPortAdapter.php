<?php

declare(strict_types=1);

namespace Nexus\Laravel\IntelligenceOperations\Adapters;

use Nexus\IntelligenceOperations\Contracts\DataDriftPortInterface;
use Nexus\QueryEngine\Contracts\AnalyticsRepositoryInterface;

final readonly class DataDriftPortAdapter implements DataDriftPortInterface
{
    public function __construct(private AnalyticsRepositoryInterface $analyticsRepository) {}

    public function calculateDriftScore(string $modelId): float
    {
        $history = $this->analyticsRepository->getHistory('model', $modelId, 50);
        if ($history === []) {
            return 0.0;
        }

        $durations = [];
        foreach ($history as $entry) {
            if (isset($entry['duration_ms'])) {
                $durations[] = (float) $entry['duration_ms'];
            }
        }

        if ($durations === []) {
            return 0.0;
        }

        $avg = array_sum($durations) / count($durations);
        $max = max($durations);

        if ($max <= 0.0) {
            return 0.0;
        }

        $score = min(1.0, max(0.0, ($max - $avg) / $max));

        return round($score, 4);
    }
}
