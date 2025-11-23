<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Contracts;

/**
 * Telemetry Tracker Interface
 *
 * Primary interface for tracking real-time performance metrics across the ERP system.
 * Supports distributed tracing context for correlation with OpenTelemetry spans.
 *
 * @package Nexus\Monitoring\Contracts
 */
interface TelemetryTrackerInterface
{
    /**
     * Record an instantaneous numerical value for a specific metric.
     * Used for metrics that can go up or down (e.g., current queue size, memory usage).
     *
     * @param string $key Metric name (e.g., 'queue_size', 'memory_usage_bytes')
     * @param float $value The instantaneous value
     * @param array<string, scalar> $tags Contextual dimensions (e.g., ['queue' => 'emails'])
     * @param string|null $traceId Optional OpenTelemetry trace ID
     * @param string|null $spanId Optional OpenTelemetry span ID
     * @return void
     */
    public function gauge(
        string $key,
        float $value,
        array $tags = [],
        ?string $traceId = null,
        ?string $spanId = null
    ): void;

    /**
     * Increment a counter by a specific amount.
     * Used for monotonically increasing values (e.g., total requests, errors).
     *
     * @param string $key Metric name (e.g., 'api_requests_total', 'errors_total')
     * @param float $value Amount to increment (default: 1.0)
     * @param array<string, scalar> $tags Contextual dimensions
     * @param string|null $traceId Optional OpenTelemetry trace ID
     * @param string|null $spanId Optional OpenTelemetry span ID
     * @return void
     */
    public function increment(
        string $key,
        float $value = 1.0,
        array $tags = [],
        ?string $traceId = null,
        ?string $spanId = null
    ): void;

    /**
     * Record the duration of a specific operation in milliseconds.
     * Used for latency and performance tracking.
     *
     * @param string $key Metric name (e.g., 'api_latency_ms', 'db_query_duration_ms')
     * @param float $milliseconds Duration in milliseconds
     * @param array<string, scalar> $tags Contextual dimensions
     * @param string|null $traceId Optional OpenTelemetry trace ID
     * @param string|null $spanId Optional OpenTelemetry span ID
     * @return void
     */
    public function timing(
        string $key,
        float $milliseconds,
        array $tags = [],
        ?string $traceId = null,
        ?string $spanId = null
    ): void;

    /**
     * Record a value for histogram distribution analysis.
     * Used for analyzing value distributions (e.g., request size distribution).
     *
     * @param string $key Metric name (e.g., 'response_size_bytes')
     * @param float $value The value to record
     * @param array<string, scalar> $tags Contextual dimensions
     * @param string|null $traceId Optional OpenTelemetry trace ID
     * @param string|null $spanId Optional OpenTelemetry span ID
     * @return void
     */
    public function histogram(
        string $key,
        float $value,
        array $tags = [],
        ?string $traceId = null,
        ?string $spanId = null
    ): void;
}
