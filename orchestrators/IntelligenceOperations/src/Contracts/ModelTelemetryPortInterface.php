<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Contracts;

interface ModelTelemetryPortInterface
{
    /**
     * @param array<string, scalar> $tags
     */
    public function increment(string $metric, float $value = 1.0, array $tags = []): void;

    /**
     * @param array<string, scalar> $tags
     */
    public function timing(string $metric, float $milliseconds, array $tags = []): void;

    /**
     * @param array<string, scalar> $tags
     */
    public function gauge(string $metric, float $value, array $tags = []): void;

    /**
     * @return array<string, float>
     */
    public function modelMetrics(string $modelId): array;
}
