<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Traits;

use Nexus\Monitoring\Contracts\TelemetryTrackerInterface;
use Nexus\Monitoring\Core\SLOWrapper;

/**
 * MonitoringAwareTrait
 *
 * Provides convenient monitoring capabilities to any class.
 * Simplifies metric tracking and SLO measurement.
 *
 * @package Nexus\Monitoring\Traits
 */
trait MonitoringAwareTrait
{
    protected ?TelemetryTrackerInterface $telemetry = null;

    /**
     * Set the telemetry tracker instance.
     *
     * @param TelemetryTrackerInterface $telemetry
     * @return void
     */
    public function setTelemetry(TelemetryTrackerInterface $telemetry): void
    {
        $this->telemetry = $telemetry;
    }

    /**
     * Get the telemetry tracker instance.
     *
     * @return TelemetryTrackerInterface|null
     */
    protected function getTelemetry(): ?TelemetryTrackerInterface
    {
        return $this->telemetry;
    }

    /**
     * Record a gauge metric.
     *
     * @param string $key
     * @param float $value
     * @param array<string, scalar> $tags
     * @return void
     */
    protected function recordGauge(string $key, float $value, array $tags = []): void
    {
        $this->telemetry?->gauge($key, $value, $tags);
    }

    /**
     * Increment a counter metric.
     *
     * @param string $key
     * @param float $value
     * @param array<string, scalar> $tags
     * @return void
     */
    protected function recordIncrement(string $key, float $value = 1.0, array $tags = []): void
    {
        $this->telemetry?->increment($key, $value, $tags);
    }

    /**
     * Record a timing metric.
     *
     * @param string $key
     * @param float $milliseconds
     * @param array<string, scalar> $tags
     * @return void
     */
    protected function recordTiming(string $key, float $milliseconds, array $tags = []): void
    {
        $this->telemetry?->timing($key, $milliseconds, $tags);
    }

    /**
     * Record a histogram metric.
     *
     * @param string $key
     * @param float $value
     * @param array<string, scalar> $tags
     * @return void
     */
    protected function recordHistogram(string $key, float $value, array $tags = []): void
    {
        $this->telemetry?->histogram($key, $value, $tags);
    }

    /**
     * Track an operation with automatic SLO metrics.
     *
     * @template T
     * @param string $operation
     * @param callable(): T $callable
     * @param array<string, scalar> $tags
     * @return T
     * @throws \Throwable
     */
    protected function trackOperation(string $operation, callable $callable, array $tags = []): mixed
    {
        if ($this->telemetry === null) {
            return $callable();
        }

        $wrapper = SLOWrapper::for($this->telemetry, $operation, $tags);
        return $wrapper->execute($callable);
    }

    /**
     * Time a callable and record the duration.
     *
     * @template T
     * @param string $metricKey
     * @param callable(): T $callable
     * @param array<string, scalar> $tags
     * @return T
     */
    protected function timeOperation(string $metricKey, callable $callable, array $tags = []): mixed
    {
        if ($this->telemetry === null) {
            return $callable();
        }

        $startTime = microtime(true);
        try {
            return $callable();
        } finally {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->telemetry->timing($metricKey, $duration, $tags);
        }
    }
}
