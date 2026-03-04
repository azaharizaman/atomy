<?php

declare(strict_types=1);

namespace Nexus\Laravel\ConnectivityOperations\Adapters;

use Nexus\ConnectivityOperations\Contracts\ConnectivityTelemetryPortInterface;
use Nexus\Telemetry\Contracts\TelemetryTrackerInterface;

final readonly class ConnectivityTelemetryPortAdapter implements ConnectivityTelemetryPortInterface
{
    public function __construct(private TelemetryTrackerInterface $telemetry) {}

    public function increment(string $metric, float $value = 1.0, array $tags = []): void
    {
        $this->telemetry->increment($metric, $value, $tags);
    }

    public function timing(string $metric, float $milliseconds, array $tags = []): void
    {
        $this->telemetry->timing($metric, $milliseconds, $tags);
    }
}
