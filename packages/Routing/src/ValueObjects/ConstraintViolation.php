<?php

declare(strict_types=1);

namespace Nexus\Routing\ValueObjects;

/**
 * Immutable value object representing a constraint violation
 * 
 * Tracks violations of capacity, time windows, or other constraints
 */
final readonly class ConstraintViolation implements \JsonSerializable
{
    public function __construct(
        public string $type,
        public string $description,
        public ?string $vehicleId = null,
        public ?string $stopId = null,
        public ?float $severity = null,
        public ?array $metadata = null
    ) {
    }

    /**
     * Check if violation is critical (severity > 0.8)
     */
    public function isCritical(): bool
    {
        return $this->severity !== null && $this->severity > 0.8;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'description' => $this->description,
            'vehicle_id' => $this->vehicleId,
            'stop_id' => $this->stopId,
            'severity' => $this->severity,
            'is_critical' => $this->isCritical(),
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
