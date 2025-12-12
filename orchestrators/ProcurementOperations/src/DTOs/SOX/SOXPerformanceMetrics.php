<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\SOX;

use Nexus\ProcurementOperations\Enums\SOXControlPoint;

/**
 * Performance metrics for SOX control validation.
 */
final readonly class SOXPerformanceMetrics
{
    /**
     * @param array<string, float> $controlDurations Duration per control (control point => ms)
     * @param array<string, int> $controlInvocations Invocation count per control
     * @param array<string, int> $controlFailures Failure count per control
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $tenantId,
        public \DateTimeImmutable $periodStart,
        public \DateTimeImmutable $periodEnd,
        public int $totalValidations,
        public float $totalDurationMs,
        public float $averageDurationMs,
        public float $p50DurationMs,
        public float $p95DurationMs,
        public float $p99DurationMs,
        public float $maxDurationMs,
        public array $controlDurations,
        public array $controlInvocations,
        public array $controlFailures,
        public int $timeoutCount,
        public int $errorCount,
        public array $metadata = [],
    ) {}

    /**
     * Check if P95 latency is within SLA (default 200ms).
     */
    public function isWithinSla(float $slaThresholdMs = 200.0): bool
    {
        return $this->p95DurationMs <= $slaThresholdMs;
    }

    /**
     * Get the slowest control points.
     *
     * @param int $limit Number of controls to return
     * @return array<string, float> Control point => average duration
     */
    public function getSlowestControls(int $limit = 5): array
    {
        arsort($this->controlDurations);

        return array_slice($this->controlDurations, 0, $limit, true);
    }

    /**
     * Get the most failing control points.
     *
     * @param int $limit Number of controls to return
     * @return array<string, int> Control point => failure count
     */
    public function getMostFailingControls(int $limit = 5): array
    {
        arsort($this->controlFailures);

        return array_slice($this->controlFailures, 0, $limit, true);
    }

    /**
     * Get failure rate for a specific control.
     */
    public function getControlFailureRate(SOXControlPoint $control): float
    {
        $key = $control->value;
        $invocations = $this->controlInvocations[$key] ?? 0;
        $failures = $this->controlFailures[$key] ?? 0;

        if ($invocations === 0) {
            return 0.0;
        }

        return round(($failures / $invocations) * 100, 2);
    }

    /**
     * Get overall error rate (timeouts + errors).
     */
    public function getErrorRatePercentage(): float
    {
        if ($this->totalValidations === 0) {
            return 0.0;
        }

        return round((($this->timeoutCount + $this->errorCount) / $this->totalValidations) * 100, 2);
    }

    /**
     * Get overall success rate.
     */
    public function getSuccessRate(): float
    {
        $totalFailures = 0;
        foreach ($this->controlFailures as $count) {
            $totalFailures += $count;
        }

        $totalInvocations = 0;
        foreach ($this->controlInvocations as $count) {
            $totalInvocations += $count;
        }

        if ($totalInvocations === 0) {
            return 100.0;
        }

        return round((1 - ($totalFailures / $totalInvocations)) * 100, 2);
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'period_start' => $this->periodStart->format(\DateTimeInterface::ATOM),
            'period_end' => $this->periodEnd->format(\DateTimeInterface::ATOM),
            'total_validations' => $this->totalValidations,
            'total_duration_ms' => $this->totalDurationMs,
            'average_duration_ms' => $this->averageDurationMs,
            'p50_duration_ms' => $this->p50DurationMs,
            'p95_duration_ms' => $this->p95DurationMs,
            'p99_duration_ms' => $this->p99DurationMs,
            'max_duration_ms' => $this->maxDurationMs,
            'control_durations' => $this->controlDurations,
            'control_invocations' => $this->controlInvocations,
            'control_failures' => $this->controlFailures,
            'timeout_count' => $this->timeoutCount,
            'error_count' => $this->errorCount,
            'is_within_sla' => $this->isWithinSla(),
            'success_rate' => $this->getSuccessRate(),
            'error_rate' => $this->getErrorRate(),
            'slowest_controls' => $this->getSlowestControls(),
            'most_failing_controls' => $this->getMostFailingControls(),
            'metadata' => $this->metadata,
        ];
    }
}
