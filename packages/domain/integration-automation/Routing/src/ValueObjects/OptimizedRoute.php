<?php

declare(strict_types=1);

namespace Nexus\Routing\ValueObjects;

use Nexus\Geo\ValueObjects\Distance;

/**
 * Immutable value object representing an optimized route
 * 
 * Contains ordered stops, total distance/duration, and metrics
 */
final readonly class OptimizedRoute implements \JsonSerializable
{
    /**
     * @param array<RouteStop> $stops Ordered array of stops
     */
    public function __construct(
        public string $routeId,
        public array $stops,
        public Distance $totalDistance,
        public int $totalDurationSeconds,
        public float $totalLoad = 0.0,
        public ?array $metadata = null
    ) {
    }

    /**
     * Get stop IDs in order
     * 
     * @return array<string>
     */
    public function getStopIds(): array
    {
        return array_map(fn(RouteStop $stop) => $stop->id, $this->stops);
    }

    /**
     * Get number of stops
     */
    public function getStopCount(): int
    {
        return count($this->stops);
    }

    /**
     * Get stop by ID
     */
    public function getStop(string $id): ?RouteStop
    {
        foreach ($this->stops as $stop) {
            if ($stop->id === $id) {
                return $stop;
            }
        }
        return null;
    }

    /**
     * Get stop by index
     */
    public function getStopAt(int $index): ?RouteStop
    {
        return $this->stops[$index] ?? null;
    }

    /**
     * Calculate total service time
     */
    public function getTotalServiceTime(): int
    {
        return array_sum(array_map(
            fn(RouteStop $stop) => $stop->serviceDurationSeconds,
            $this->stops
        ));
    }

    /**
     * Calculate travel time (excluding service time)
     */
    public function getTravelTime(): int
    {
        return $this->totalDurationSeconds - $this->getTotalServiceTime();
    }

    /**
     * Format total duration as HH:MM:SS
     */
    public function formatDuration(): string
    {
        return gmdate('H:i:s', $this->totalDurationSeconds);
    }

    public function toArray(): array
    {
        return [
            'route_id' => $this->routeId,
            'stops' => array_map(fn(RouteStop $s) => $s->toArray(), $this->stops),
            'stop_count' => $this->getStopCount(),
            'total_distance' => $this->totalDistance->toArray(),
            'total_duration_seconds' => $this->totalDurationSeconds,
            'total_duration_formatted' => $this->formatDuration(),
            'total_load' => $this->totalLoad,
            'travel_time_seconds' => $this->getTravelTime(),
            'service_time_seconds' => $this->getTotalServiceTime(),
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create OptimizedRoute from array representation
     */
    public static function fromArray(array $data): self
    {
        return new self(
            routeId: $data['route_id'],
            stops: array_map(fn($s) => RouteStop::fromArray($s), $data['stops']),
            totalDistance: Distance::fromArray($data['total_distance']),
            totalDurationSeconds: $data['total_duration_seconds'],
            totalLoad: $data['total_load'] ?? 0.0,
            metadata: $data['metadata'] ?? null
        );
    }
}
