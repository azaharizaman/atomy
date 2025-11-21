<?php

declare(strict_types=1);

namespace Nexus\Routing\ValueObjects;

use Nexus\Geo\ValueObjects\Distance;

/**
 * Immutable value object representing optimization performance metrics
 * 
 * Tracks execution time, improvements, and constraint violations
 */
final readonly class OptimizationMetrics implements \JsonSerializable
{
    /**
     * @param array<ConstraintViolation> $violations
     */
    public function __construct(
        public float $executionTimeMs,
        public string $algorithm,
        public int $totalStops,
        public int $totalVehicles,
        public Distance $totalDistance,
        public int $totalDurationSeconds,
        public ?Distance $initialDistance = null,
        public ?int $initialDurationSeconds = null,
        public array $violations = [],
        public ?array $metadata = null
    ) {
    }

    /**
     * Calculate improvement percentage over initial solution
     */
    public function getDistanceImprovement(): ?float
    {
        if ($this->initialDistance === null) {
            return null;
        }

        $initial = $this->initialDistance->meters;
        $optimized = $this->totalDistance->meters;

        if ($initial === 0) {
            return null;
        }

        return (($initial - $optimized) / $initial) * 100;
    }

    /**
     * Calculate duration improvement percentage
     */
    public function getDurationImprovement(): ?float
    {
        if ($this->initialDurationSeconds === null) {
            return null;
        }

        if ($this->initialDurationSeconds === 0) {
            return null;
        }

        return (($this->initialDurationSeconds - $this->totalDurationSeconds) / 
                $this->initialDurationSeconds) * 100;
    }

    /**
     * Get violation count
     */
    public function getViolationCount(): int
    {
        return count($this->violations);
    }

    /**
     * Get critical violation count
     */
    public function getCriticalViolationCount(): int
    {
        return count(array_filter(
            $this->violations,
            fn(ConstraintViolation $v) => $v->isCritical()
        ));
    }

    /**
     * Check if optimization was successful (no critical violations)
     */
    public function isSuccessful(): bool
    {
        return $this->getCriticalViolationCount() === 0;
    }

    public function toArray(): array
    {
        return [
            'execution_time_ms' => round($this->executionTimeMs, 2),
            'algorithm' => $this->algorithm,
            'total_stops' => $this->totalStops,
            'total_vehicles' => $this->totalVehicles,
            'total_distance' => $this->totalDistance->toArray(),
            'total_duration_seconds' => $this->totalDurationSeconds,
            'total_duration_formatted' => gmdate('H:i:s', $this->totalDurationSeconds),
            'initial_distance' => $this->initialDistance?->toArray(),
            'initial_duration_seconds' => $this->initialDurationSeconds,
            'distance_improvement_pct' => $this->getDistanceImprovement() !== null 
                ? round($this->getDistanceImprovement(), 2) 
                : null,
            'duration_improvement_pct' => $this->getDurationImprovement() !== null
                ? round($this->getDurationImprovement(), 2)
                : null,
            'violation_count' => $this->getViolationCount(),
            'critical_violation_count' => $this->getCriticalViolationCount(),
            'is_successful' => $this->isSuccessful(),
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
