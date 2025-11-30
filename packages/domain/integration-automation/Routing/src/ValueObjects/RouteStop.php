<?php

declare(strict_types=1);

namespace Nexus\Routing\ValueObjects;

use Nexus\Geo\ValueObjects\Coordinates;

/**
 * Immutable value object representing a route stop
 * 
 * Contains location, time windows, service duration, and demand
 */
final readonly class RouteStop implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public Coordinates $coordinates,
        public ?\DateTimeImmutable $timeWindowStart = null,
        public ?\DateTimeImmutable $timeWindowEnd = null,
        public int $serviceDurationSeconds = 0,
        public float $demand = 0.0,
        public ?array $metadata = null
    ) {
    }

    /**
     * Check if stop has time window constraint
     */
    public function hasTimeWindow(): bool
    {
        return $this->timeWindowStart !== null && $this->timeWindowEnd !== null;
    }

    /**
     * Check if arrival time is within window
     */
    public function isArrivalValid(\DateTimeImmutable $arrivalTime): bool
    {
        if (!$this->hasTimeWindow()) {
            return true;
        }

        return $arrivalTime >= $this->timeWindowStart && $arrivalTime <= $this->timeWindowEnd;
    }

    /**
     * Get time window duration in seconds
     */
    public function getTimeWindowDuration(): ?int
    {
        if (!$this->hasTimeWindow()) {
            return null;
        }

        return $this->timeWindowEnd->getTimestamp() - $this->timeWindowStart->getTimestamp();
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
            'coordinates' => $this->coordinates->toArray(),
            'time_window_start' => $this->timeWindowStart?->format('Y-m-d H:i:s'),
            'time_window_end' => $this->timeWindowEnd?->format('Y-m-d H:i:s'),
            'service_duration_seconds' => $this->serviceDurationSeconds,
            'demand' => $this->demand,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create from array representation
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            coordinates: Coordinates::fromArray($data['coordinates']),
            timeWindowStart: $data['time_window_start'] ? new \DateTimeImmutable($data['time_window_start']) : null,
            timeWindowEnd: $data['time_window_end'] ? new \DateTimeImmutable($data['time_window_end']) : null,
            serviceDurationSeconds: $data['service_duration_seconds'] ?? 0,
            demand: $data['demand'] ?? 0.0,
            metadata: $data['metadata'] ?? null
        );
    }
}
