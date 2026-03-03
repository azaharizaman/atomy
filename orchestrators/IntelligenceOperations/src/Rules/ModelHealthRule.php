<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Rules;

final class ModelHealthRule
{
    public function status(float $accuracy, float $latencyMs, float $driftScore): string
    {
        if ($accuracy < 0.75 || $driftScore > 0.30 || $latencyMs > 600.0) {
            return 'critical';
        }

        if ($accuracy < 0.85 || $driftScore > 0.15 || $latencyMs > 250.0) {
            return 'degraded';
        }

        return 'healthy';
    }
}
