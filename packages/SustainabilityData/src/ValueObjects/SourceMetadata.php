<?php

declare(strict_types=1);

namespace Nexus\SustainabilityData\ValueObjects;

use Nexus\Common\Contracts\SerializableVO;

/**
 * Immutable value object representing the metadata of a sustainability data source.
 */
final readonly class SourceMetadata implements SerializableVO
{
    /**
     * @param string $sourceId Unique identifier for the source
     * @param string $type Type of source: 'iot', 'manual', 'utility', 'satellite'
     * @param string $provider Name of the provider or manufacturer
     * @param array<string, mixed> $attributes Additional fixed attributes (e.g., location, model)
     */
    public function __construct(
        public string $sourceId,
        public string $type,
        public string $provider,
        public array $attributes = []
    ) {
        $allowedTypes = ['iot', 'manual', 'utility', 'satellite'];
        if (!in_array($this->type, $allowedTypes, true)) {
            throw new \InvalidArgumentException("Invalid source type: {$this->type}");
        }
    }

    public function toArray(): array
    {
        return [
            'source_id' => $this->sourceId,
            'type' => $this->type,
            'provider' => $this->provider,
            'attributes' => $this->attributes,
        ];
    }

    public function toString(): string
    {
        return "{$this->provider} {$this->type} (#{$this->sourceId})";
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public static function fromArray(array $data): static
    {
        return new self(
            sourceId: $data['source_id'],
            type: $data['type'],
            provider: $data['provider'],
            attributes: $data['attributes'] ?? []
        );
    }
}
