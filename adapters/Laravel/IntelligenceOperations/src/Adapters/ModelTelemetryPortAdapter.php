<?php

declare(strict_types=1);

namespace Nexus\Laravel\IntelligenceOperations\Adapters;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Nexus\IntelligenceOperations\Contracts\ModelTelemetryPortInterface;
use Nexus\Telemetry\Contracts\TelemetryTrackerInterface;

final readonly class ModelTelemetryPortAdapter implements ModelTelemetryPortInterface
{
    private const CACHE_PREFIX = 'intelligence:model:metrics:';

    public function __construct(
        private TelemetryTrackerInterface $telemetry,
        private CacheRepository $cache,
    ) {}

    public function increment(string $metric, float $value = 1.0, array $tags = []): void
    {
        $this->telemetry->increment($metric, $value, $tags);

        if (isset($tags['model_id'])) {
            $this->updateModelMetric((string) $tags['model_id'], $metric, $value);
        }
    }

    public function timing(string $metric, float $milliseconds, array $tags = []): void
    {
        $this->telemetry->timing($metric, $milliseconds, $tags);

        if (isset($tags['model_id'])) {
            $modelId = (string) $tags['model_id'];
            $current = $this->modelMetrics($modelId);
            $current['latency_ms'] = $milliseconds;
            $this->cache->put(self::CACHE_PREFIX . $modelId, $current, 86400);
        }
    }

    public function gauge(string $metric, float $value, array $tags = []): void
    {
        $this->telemetry->gauge($metric, $value, $tags);

        if (isset($tags['model_id'])) {
            $modelId = (string) $tags['model_id'];
            $current = $this->modelMetrics($modelId);

            if (str_contains($metric, 'accuracy')) {
                $current['accuracy'] = $value;
            }

            if (str_contains($metric, 'drift')) {
                $current['drift_score'] = $value;
            }

            $this->cache->put(self::CACHE_PREFIX . $modelId, $current, 86400);
        }
    }

    public function modelMetrics(string $modelId): array
    {
        $raw = $this->cache->get(self::CACHE_PREFIX . $modelId);
        if (!is_array($raw)) {
            return [
                'accuracy' => 0.90,
                'latency_ms' => 100.0,
                'drift_score' => 0.0,
            ];
        }

        return [
            'accuracy' => (float) ($raw['accuracy'] ?? 0.90),
            'latency_ms' => (float) ($raw['latency_ms'] ?? 100.0),
            'drift_score' => (float) ($raw['drift_score'] ?? 0.0),
        ];
    }

    private function updateModelMetric(string $modelId, string $metric, float $value): void
    {
        $current = $this->modelMetrics($modelId);

        if (str_contains($metric, 'deploy.success')) {
            $current['accuracy'] = max($current['accuracy'], 0.90);
        }

        $this->cache->put(self::CACHE_PREFIX . $modelId, $current, 86400);
    }
}
