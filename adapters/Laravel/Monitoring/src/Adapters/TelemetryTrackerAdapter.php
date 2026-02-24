<?php

declare(strict_types=1);

namespace Nexus\Laravel\Monitoring\Adapters;

use Nexus\Monitoring\Contracts\TelemetryTrackerInterface;
use Psr\Log\LoggerInterface;

/**
 * Laravel implementation of TelemetryTrackerInterface.
 *
 * Uses Laravel's logger for telemetry tracking.
 */
class TelemetryTrackerAdapter implements TelemetryTrackerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function gauge(
        string $key,
        float $value,
        array $tags = [],
        ?string $traceId = null,
        ?string $spanId = null
    ): void {
        $this->logger->debug('Telemetry gauge', [
            'metric' => $key,
            'value' => $value,
            'tags' => $tags,
            'trace_id' => $traceId,
            'span_id' => $spanId,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function increment(
        string $key,
        float $value = 1.0,
        array $tags = [],
        ?string $traceId = null,
        ?string $spanId = null
    ): void {
        $this->logger->debug('Telemetry increment', [
            'metric' => $key,
            'value' => $value,
            'tags' => $tags,
            'trace_id' => $traceId,
            'span_id' => $spanId,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function timing(
        string $key,
        float $milliseconds,
        array $tags = [],
        ?string $traceId = null,
        ?string $spanId = null
    ): void {
        $this->logger->debug('Telemetry timing', [
            'metric' => $key,
            'value' => $milliseconds,
            'unit' => 'ms',
            'tags' => $tags,
            'trace_id' => $traceId,
            'span_id' => $spanId,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function histogram(
        string $key,
        float $value,
        array $tags = [],
        ?string $traceId = null,
        ?string $spanId = null
    ): void {
        $this->logger->debug('Telemetry histogram', [
            'metric' => $key,
            'value' => $value,
            'tags' => $tags,
            'trace_id' => $traceId,
            'span_id' => $spanId,
        ]);
    }
}
