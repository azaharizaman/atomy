<?php

declare(strict_types=1);

namespace Nexus\Geo\ValueObjects;

/**
 * Immutable value object representing a bounding box (rectangular geographic area)
 * 
 * Used for geofencing and region queries
 */
final readonly class BoundingBox implements \JsonSerializable
{
    public function __construct(
        public Coordinates $southwest,
        public Coordinates $northeast
    ) {
    }

    /**
     * Create from coordinates array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Coordinates::fromArray($data['southwest']),
            Coordinates::fromArray($data['northeast'])
        );
    }

    /**
     * Check if coordinates are within this bounding box
     */
    public function contains(Coordinates $coordinates): bool
    {
        return $coordinates->latitude >= $this->southwest->latitude
            && $coordinates->latitude <= $this->northeast->latitude
            && $coordinates->longitude >= $this->southwest->longitude
            && $coordinates->longitude <= $this->northeast->longitude;
    }

    /**
     * Calculate center point of bounding box
     */
    public function getCenter(): Coordinates
    {
        $centerLat = ($this->southwest->latitude + $this->northeast->latitude) / 2;
        $centerLng = ($this->southwest->longitude + $this->northeast->longitude) / 2;

        return new Coordinates($centerLat, $centerLng);
    }

    /**
     * Get bounding box as array
     */
    public function toArray(): array
    {
        return [
            'southwest' => $this->southwest->toArray(),
            'northeast' => $this->northeast->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
