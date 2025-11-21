<?php

declare(strict_types=1);

namespace Nexus\Assets\ValueObjects;

use DateTimeInterface;
use JsonSerializable;

/**
 * Asset Custody Value Object
 *
 * Tracks who has custody of an asset and where it is located.
 */
final readonly class AssetCustody implements JsonSerializable
{
    public function __construct(
        public string $userId,
        public string|object $location,
        public DateTimeInterface $assignedDate,
        public ?string $gpsCoordinates = null,
        public ?string $notes = null
    ) {}

    /**
     * Get location as string
     */
    public function getLocationString(): string
    {
        if (is_string($this->location)) {
            return $this->location;
        }

        // Assume LocationInterface with __toString()
        return (string)$this->location;
    }

    /**
     * Check if has GPS coordinates
     */
    public function hasGpsCoordinates(): bool
    {
        return $this->gpsCoordinates !== null;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'location' => $this->getLocationString(),
            'assigned_date' => $this->assignedDate->format('Y-m-d H:i:s'),
            'gps_coordinates' => $this->gpsCoordinates,
            'notes' => $this->notes,
        ];
    }

    /**
     * JSON serialization
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
