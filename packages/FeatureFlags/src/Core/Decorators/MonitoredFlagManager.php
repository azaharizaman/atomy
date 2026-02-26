<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Core\Decorators;

use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
use Nexus\FeatureFlags\Contracts\FlagAuditQueryInterface;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;

/**
 * Monitoring decorator for FeatureFlagManagerInterface.
 *
 * Tracks metrics using TelemetryTrackerInterface from Nexus\Telemetry.
 *
 * Metrics tracked:
 * - flag_evaluation_duration_ms (timing)
 * - flag_evaluation_total (counter)
 * - flag_override_active_count (gauge)
 * - flag_custom_evaluator_errors_total (counter)
 * - flag_not_found_total (counter)
 * - bulk_evaluation_duration_ms (timing)
 * - bulk_evaluation_count (counter)
 *
 * Optional Dependency: If TelemetryTrackerInterface is not available,
 * this decorator acts as a pass-through (no-op).
 */
final readonly class MonitoredFlagManager implements FeatureFlagManagerInterface
{
    /**
     * @param FeatureFlagManagerInterface $inner Inner manager
     * @param object|null $telemetry Optional telemetry tracker (TelemetryTrackerInterface)
     */
    public function __construct(
        private FeatureFlagManagerInterface $inner,
        private ?object $telemetry = null
    ) {
    }

    public function isEnabled(
        string $name,
        array|EvaluationContext $context = [],
        bool $defaultIfNotFound = false
    ): bool {
        $startTime = microtime(true);

        try {
            $result = $this->inner->isEnabled($name, $context, $defaultIfNotFound);

            // Track success metrics
            $this->trackTiming(
                'flag_evaluation_duration_ms',
                $startTime,
                ['flag_name' => $name, 'result' => $result ? 'true' : 'false']
            );

            $this->increment('flag_evaluation_total', 1, [
                'flag_name' => $name,
                'result' => $result ? 'true' : 'false',
            ]);

            return $result;
        } catch (\Throwable $e) {
            // Track error metrics
            $this->increment('flag_evaluation_errors_total', 1, [
                'flag_name' => $name,
                'error_type' => get_class($e),
            ]);

            throw $e;
        }
    }

    public function isDisabled(
        string $name,
        array|EvaluationContext $context = [],
        bool $defaultIfNotFound = false
    ): bool {
        // Delegate to isEnabled (already tracked)
        return !$this->isEnabled($name, $context, $defaultIfNotFound);
    }

    public function evaluateMany(
        array $flagNames,
        array|EvaluationContext $context = []
    ): array {
        $startTime = microtime(true);
        $count = count($flagNames);

        try {
            $results = $this->inner->evaluateMany($flagNames, $context);

            // Track bulk evaluation metrics
            $this->trackTiming(
                'bulk_evaluation_duration_ms',
                $startTime,
                ['flag_count' => (string)$count]
            );

            $this->increment('bulk_evaluation_total', 1, [
                'flag_count' => (string)$count,
            ]);

            // Track individual flag results
            $trueCount = count(array_filter($results, fn($v) => $v === true));
            $falseCount = $count - $trueCount;

            $this->gauge('bulk_evaluation_true_count', (float)$trueCount);
            $this->gauge('bulk_evaluation_false_count', (float)$falseCount);

            return $results;
        } catch (\Throwable $e) {
            $this->increment('bulk_evaluation_errors_total', 1, [
                'flag_count' => (string)$count,
                'error_type' => get_class($e),
            ]);

            throw $e;
        }
    }

    public function hasAuditChange(): bool
    {
        return $this->inner->hasAuditChange();
    }

    public function hasAuditQuery(): bool
    {
        return $this->inner->hasAuditQuery();
    }

    public function getAuditQuery(): ?FlagAuditQueryInterface
    {
        return $this->inner->getAuditQuery();
    }

    /**
     * Track timing metric.
     *
     * @param string $key Metric name
     * @param float $startTime Start time from microtime(true)
     * @param array<string, string> $tags Additional tags
     */
    private function trackTiming(string $key, float $startTime, array $tags = []): void
    {
        if ($this->telemetry === null) {
            return;
        }

        if (!method_exists($this->telemetry, 'timing')) {
            return;
        }

        $durationMs = (microtime(true) - $startTime) * 1000;

        $this->telemetry->timing($key, $durationMs, $tags);
    }

    /**
     * Increment counter metric.
     *
     * @param string $key Metric name
     * @param float $value Increment value
     * @param array<string, string> $tags Additional tags
     */
    private function increment(string $key, float $value = 1.0, array $tags = []): void
    {
        if ($this->telemetry === null) {
            return;
        }

        if (!method_exists($this->telemetry, 'increment')) {
            return;
        }

        $this->telemetry->increment($key, $value, $tags);
    }

    /**
     * Set gauge metric.
     *
     * @param string $key Metric name
     * @param float $value Gauge value
     * @param array<string, string> $tags Additional tags
     */
    private function gauge(string $key, float $value, array $tags = []): void
    {
        if ($this->telemetry === null) {
            return;
        }

        if (!method_exists($this->telemetry, 'gauge')) {
            return;
        }

        $this->telemetry->gauge($key, $value, $tags);
    }
}
