<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Rules;

final class ModelHealthRule
{
    private const CRITICAL_ACCURACY_THRESHOLD = 0.75;
    private const CRITICAL_DRIFT_THRESHOLD = 0.30;
    private const CRITICAL_LATENCY_MS = 600.0;
    private const DEGRADED_ACCURACY_THRESHOLD = 0.85;
    private const DEGRADED_DRIFT_THRESHOLD = 0.15;
    private const DEGRADED_LATENCY_MS = 250.0;

    public function status(float $accuracy, float $latencyMs, float $driftScore): string
    {
        if (
            $accuracy < self::CRITICAL_ACCURACY_THRESHOLD ||
            $driftScore > self::CRITICAL_DRIFT_THRESHOLD ||
            $latencyMs > self::CRITICAL_LATENCY_MS
        ) {
            return 'critical';
        }

        if (
            $accuracy < self::DEGRADED_ACCURACY_THRESHOLD ||
            $driftScore > self::DEGRADED_DRIFT_THRESHOLD ||
            $latencyMs > self::DEGRADED_LATENCY_MS
        ) {
            return 'degraded';
        }

        return 'healthy';
    }
}
