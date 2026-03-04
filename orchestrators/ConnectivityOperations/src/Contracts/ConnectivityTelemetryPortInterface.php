<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Contracts;

interface ConnectivityTelemetryPortInterface
{
    /**
     * @param array<string, scalar> $tags
     */
    public function increment(string $metric, float $value = 1.0, array $tags = []): void;

    /**
     * @param array<string, scalar> $tags
     */
    public function timing(string $metric, float $milliseconds, array $tags = []): void;
}
