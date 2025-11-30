<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

/**
 * EventSerializerInterface
 *
 * Contract for serializing and deserializing event payloads.
 * Supports multiple formats (JSON, Avro, Protobuf).
 *
 * Requirements satisfied:
 * - ARC-EVS-7011: Define EventSerializerInterface for event payload serialization
 *
 * @package Nexus\EventStream\Contracts
 */
interface EventSerializerInterface
{
    /**
     * Serialize event payload to string
     *
     * @param array<string, mixed> $payload The event payload
     * @return string Serialized payload
     */
    public function serialize(array $payload): string;

    /**
     * Deserialize payload string to array
     *
     * @param string $serialized The serialized payload
     * @return array<string, mixed> Deserialized payload
     */
    public function deserialize(string $serialized): array;

    /**
     * Get the serializer format (json, avro, protobuf, etc.)
     *
     * @return string
     */
    public function getFormat(): string;
}
