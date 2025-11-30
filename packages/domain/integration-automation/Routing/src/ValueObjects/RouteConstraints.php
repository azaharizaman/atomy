<?php

declare(strict_types=1);

namespace Nexus\Routing\ValueObjects;

/**
 * Immutable value object representing route optimization constraints
 * 
 * Defines limits for duration, capacity, and other operational constraints
 */
final readonly class RouteConstraints implements \JsonSerializable
{
    public function __construct(
        public ?int $maxDurationSeconds = null,
        public ?float $maxCapacity = null,
        public ?int $maxStops = null,
        public ?float $maxDistanceKm = null,
        public bool $enforceTimeWindows = true,
        public bool $returnToDepot = true
    ) {
    }

    /**
     * Check if duration exceeds limit
     */
    public function isDurationExceeded(int $durationSeconds): bool
    {
        return $this->maxDurationSeconds !== null && $durationSeconds > $this->maxDurationSeconds;
    }

    /**
     * Check if capacity exceeds limit
     */
    public function isCapacityExceeded(float $load): bool
    {
        return $this->maxCapacity !== null && $load > $this->maxCapacity;
    }

    /**
     * Check if stop count exceeds limit
     */
    public function areStopsExceeded(int $stopCount): bool
    {
        return $this->maxStops !== null && $stopCount > $this->maxStops;
    }

    /**
     * Check if distance exceeds limit
     */
    public function isDistanceExceeded(float $distanceKm): bool
    {
        return $this->maxDistanceKm !== null && $distanceKm > $this->maxDistanceKm;
    }

    public function toArray(): array
    {
        return [
            'max_duration_seconds' => $this->maxDurationSeconds,
            'max_capacity' => $this->maxCapacity,
            'max_stops' => $this->maxStops,
            'max_distance_km' => $this->maxDistanceKm,
            'enforce_time_windows' => $this->enforceTimeWindows,
            'return_to_depot' => $this->returnToDepot,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
