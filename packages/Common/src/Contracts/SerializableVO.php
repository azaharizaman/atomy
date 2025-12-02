<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that can be serialized/deserialized.
 */
interface SerializableVO
{
    /**
     * Convert to array representation.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Convert to string representation.
     */
    public function toString(): string;

    /**
     * Create instance from array.
     * 
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static;
}
