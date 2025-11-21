<?php

declare(strict_types=1);

namespace Nexus\Geo\ValueObjects;

use Nexus\Geo\Exceptions\InvalidCoordinatesException;

/**
 * Immutable value object representing geographic coordinates
 * 
 * Precision: decimal(10,7) provides ~11mm accuracy
 */
final readonly class Coordinates implements \JsonSerializable
{
    public function __construct(
        public float $latitude,
        public float $longitude
    ) {
        if ($latitude < -90 || $latitude > 90) {
            throw InvalidCoordinatesException::invalidLatitude($latitude);
        }

        if ($longitude < -180 || $longitude > 180) {
            throw InvalidCoordinatesException::invalidLongitude($longitude);
        }
    }

    /**
     * Create from array [lat, lng]
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            if (!isset($data[0]) || !isset($data[1])) {
                throw InvalidCoordinatesException::invalidFormat(
                    'Array must contain latitude/longitude or [0] => lat, [1] => lng'
                );
            }
            return new self((float) $data[0], (float) $data[1]);
        }

        return new self((float) $data['latitude'], (float) $data['longitude']);
    }

    /**
     * Create from string "lat,lng"
     */
    public static function fromString(string $coordinates): self
    {
        $parts = explode(',', $coordinates);
        if (count($parts) !== 2) {
            throw InvalidCoordinatesException::invalidFormat(
                'String must be in format "latitude,longitude"'
            );
        }

        return new self((float) trim($parts[0]), (float) trim($parts[1]));
    }

    /**
     * Get coordinates as array
     */
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    /**
     * Get coordinates as string "lat,lng"
     */
    public function toString(): string
    {
        return "{$this->latitude},{$this->longitude}";
    }

    /**
     * Check if coordinates are equal
     */
    public function equals(self $other): bool
    {
        return abs($this->latitude - $other->latitude) < 0.0000001
            && abs($this->longitude - $other->longitude) < 0.0000001;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
