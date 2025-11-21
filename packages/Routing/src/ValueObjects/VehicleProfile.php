<?php

declare(strict_types=1);

namespace Nexus\Routing\ValueObjects;

use Nexus\Geo\ValueObjects\Coordinates;

/**
 * Immutable value object representing a vehicle profile
 * 
 * Defines vehicle capacity, depot location, and operating characteristics
 */
final readonly class VehicleProfile implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public float $capacity,
        public Coordinates $depotCoordinates,
        public int $maxDurationSeconds = 28800, // 8 hours default
        public float $averageSpeedKmh = 50.0,
        public ?array $metadata = null
    ) {
    }

    /**
     * Check if vehicle can handle load
     */
    public function canHandle(float $load): bool
    {
        return $load <= $this->capacity;
    }

    /**
     * Get available capacity after loading
     */
    public function getAvailableCapacity(float $currentLoad): float
    {
        return max(0, $this->capacity - $currentLoad);
    }

    /**
     * Get metadata field
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'capacity' => $this->capacity,
            'depot_coordinates' => $this->depotCoordinates->toArray(),
            'max_duration_seconds' => $this->maxDurationSeconds,
            'average_speed_kmh' => $this->averageSpeedKmh,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
