<?php

declare(strict_types=1);

namespace Nexus\EventStream\Core\Engine;

use Nexus\EventStream\Contracts\EventSerializerInterface;
use Nexus\EventStream\Exceptions\EventSerializationException;

/**
 * JsonEventSerializer
 *
 * Default JSON serializer for event payloads.
 * This is an internal implementation - applications can provide their own serializers.
 *
 * Requirements satisfied:
 * - ARC-EVS-7011: Define EventSerializerInterface for event payload serialization
 *
 * @package Nexus\EventStream\Core\Engine
 */
final readonly class JsonEventSerializer implements EventSerializerInterface
{
    public function serialize(array $payload): string
    {
        try {
            return json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        } catch (\JsonException $e) {
            throw new EventSerializationException(
                'unknown',
                'Failed to serialize event payload to JSON',
                previous: $e
            );
        }
    }

    public function deserialize(string $serialized): array
    {
        try {
            return json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new EventSerializationException(
                'unknown',
                'Failed to deserialize event payload from JSON',
                previous: $e
            );
        }
    }

    public function getFormat(): string
    {
        return 'json';
    }
}
