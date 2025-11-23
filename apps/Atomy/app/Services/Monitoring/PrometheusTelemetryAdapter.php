<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use Nexus\Monitoring\Contracts\TelemetryTrackerInterface;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;

/**
 * Prometheus Telemetry Adapter
 *
 * Implements Nexus\Monitoring's universal TelemetryTrackerInterface using Prometheus PHP Client.
 * This adapter bridges the framework-agnostic monitoring contract with Prometheus TSDB.
 *
 * Architecture:
 * - Nexus\EventStream services consume TelemetryTrackerInterface (vendor-agnostic)
 * - Atomy binds TelemetryTrackerInterface to this Prometheus implementation
 * - Switching to DataDog/New Relic only requires changing this binding
 *
 * Requirements satisfied:
 * - OPS-EVS-7501: EventStream metrics collection via universal interface
 * - OPS-EVS-7502: Prometheus metrics export capability
 * - REL-EVS-7503: Distributed metrics storage (Redis-backed registry)
 */
final readonly class PrometheusTelemetryAdapter implements TelemetryTrackerInterface
{
    private const NAMESPACE = 'eventstream';

    /**
     * @param CollectorRegistry $registry Prometheus metrics registry (Redis or InMemory storage)
     */
    public function __construct(
        private CollectorRegistry $registry
    ) {}

    /**
     * {@inheritDoc}
     */
    public function gauge(
        string $key,
        float $value,
        array $tags = [],
        ?string $traceId = null,
        ?string $spanId = null
    ): void {
        $gauge = $this->getOrRegisterGauge($key, 'Gauge metric', array_keys($tags));
        $gauge->set($value, array_values($tags));
    }

    /**
     * {@inheritDoc}
     */
    public function increment(
        string $key,
        float $value = 1.0,
        array $tags = [],
        ?string $traceId = null,
        ?string $spanId = null
    ): void {
        $counter = $this->getOrRegisterCounter($key, 'Counter metric', array_keys($tags));
        $counter->incBy($value, array_values($tags));
    }

    /**
     * {@inheritDoc}
     */
    public function timing(
        string $key,
        float $milliseconds,
        array $tags = [],
        ?string $traceId = null,
        ?string $spanId = null
    ): void {
        // Use histogram for timing metrics to track p50/p95/p99
        $histogram = $this->getOrRegisterHistogram(
            $key,
            'Timing metric in milliseconds',
            array_keys($tags),
            $this->getTimingBuckets()
        );
        $histogram->observe($milliseconds, array_values($tags));
    }

    /**
     * {@inheritDoc}
     */
    public function histogram(
        string $key,
        float $value,
        array $tags = [],
        ?string $traceId = null,
        ?string $spanId = null
    ): void {
        $histogram = $this->getOrRegisterHistogram(
            $key,
            'Histogram metric',
            array_keys($tags),
            $this->getDefaultBuckets()
        );
        $histogram->observe($value, array_values($tags));
    }

    /**
     * Export metrics in Prometheus text format.
     *
     * @return string Prometheus exposition format
     */
    public function exportPrometheusMetrics(): string
    {
        $renderer = new \Prometheus\RenderTextFormat();
        return $renderer->render($this->registry->getMetricFamilySamples());
    }

    /**
     * Get or register a Prometheus Counter.
     *
     * @param string $name Metric name
     * @param string $help Metric description
     * @param array<string> $labels Label names
     * @return Counter
     */
    private function getOrRegisterCounter(string $name, string $help, array $labels): Counter
    {
        return $this->registry->getOrRegisterCounter(
            self::NAMESPACE,
            $name,
            $help,
            $labels
        );
    }

    /**
     * Get or register a Prometheus Gauge.
     *
     * @param string $name Metric name
     * @param string $help Metric description
     * @param array<string> $labels Label names
     * @return Gauge
     */
    private function getOrRegisterGauge(string $name, string $help, array $labels): Gauge
    {
        return $this->registry->getOrRegisterGauge(
            self::NAMESPACE,
            $name,
            $help,
            $labels
        );
    }

    /**
     * Get or register a Prometheus Histogram.
     *
     * @param string $name Metric name
     * @param string $help Metric description
     * @param array<string> $labels Label names
     * @param array<float>|null $buckets Histogram buckets
     * @return Histogram
     */
    private function getOrRegisterHistogram(
        string $name,
        string $help,
        array $labels,
        ?array $buckets = null
    ): Histogram {
        return $this->registry->getOrRegisterHistogram(
            self::NAMESPACE,
            $name,
            $help,
            $labels,
            $buckets
        );
    }

    /**
     * Get timing-optimized histogram buckets (1ms to 5 seconds).
     *
     * @return array<float>
     */
    private function getTimingBuckets(): array
    {
        return [1, 5, 10, 25, 50, 100, 250, 500, 1000, 2500, 5000];
    }

    /**
     * Get default histogram buckets for general metrics.
     *
     * @return array<float>
     */
    private function getDefaultBuckets(): array
    {
        return [1, 10, 50, 100, 500, 1000, 2500, 5000, 10000, 30000];
    }
}
