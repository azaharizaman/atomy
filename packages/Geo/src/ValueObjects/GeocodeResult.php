<?php

declare(strict_types=1);

namespace Nexus\Geo\ValueObjects;

/**
 * Immutable value object representing geocoding result
 * 
 * Contains coordinates, formatted address, and provider metadata
 */
final readonly class GeocodeResult implements \JsonSerializable
{
    public function __construct(
        public Coordinates $coordinates,
        public string $formattedAddress,
        public string $provider,
        public ?BoundingBox $boundingBox = null,
        public ?array $metadata = null
    ) {
    }

    /**
     * Check if result has bounding box
     */
    public function hasBoundingBox(): bool
    {
        return $this->boundingBox !== null;
    }

    /**
     * Get specific metadata field
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function toArray(): array
    {
        return [
            'coordinates' => $this->coordinates->toArray(),
            'formatted_address' => $this->formattedAddress,
            'provider' => $this->provider,
            'bounding_box' => $this->boundingBox?->toArray(),
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
