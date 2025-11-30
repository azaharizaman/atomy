<?php

declare(strict_types=1);

namespace Nexus\Geo\ValueObjects;

/**
 * Immutable value object representing travel time/distance matrix
 * 
 * Used for routing optimization to cache calculated distances/times between points
 */
final readonly class TravelTimeMatrix implements \JsonSerializable
{
    /**
     * @param array<string, array<string, array{distance: Distance, duration: int}>> $matrix
     */
    public function __construct(
        public array $matrix
    ) {
    }

    /**
     * Get travel information from origin to destination
     * 
     * @return array{distance: Distance, duration: int}|null
     */
    public function get(string $fromId, string $toId): ?array
    {
        return $this->matrix[$fromId][$toId] ?? null;
    }

    /**
     * Get distance between two points
     */
    public function getDistance(string $fromId, string $toId): ?Distance
    {
        return $this->matrix[$fromId][$toId]['distance'] ?? null;
    }

    /**
     * Get duration between two points (in seconds)
     */
    public function getDuration(string $fromId, string $toId): ?int
    {
        return $this->matrix[$fromId][$toId]['duration'] ?? null;
    }

    /**
     * Check if matrix has entry for route
     */
    public function has(string $fromId, string $toId): bool
    {
        return isset($this->matrix[$fromId][$toId]);
    }

    /**
     * Get all destination IDs from origin
     */
    public function getDestinations(string $fromId): array
    {
        return array_keys($this->matrix[$fromId] ?? []);
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->matrix as $fromId => $destinations) {
            foreach ($destinations as $toId => $data) {
                $result[$fromId][$toId] = [
                    'distance' => $data['distance']->toArray(),
                    'duration' => $data['duration'],
                ];
            }
        }
        return $result;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
