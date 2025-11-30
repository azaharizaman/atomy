<?php

declare(strict_types=1);

namespace Nexus\EventStream\ValueObjects;

use Symfony\Component\Uid\Ulid;

/**
 * StreamId
 *
 * Value object representing a unique stream identifier.
 *
 * Requirements satisfied:
 * - ARC-EVS-7009: Use Value Objects for StreamId
 *
 * @package Nexus\EventStream\ValueObjects
 */
final readonly class StreamId
{
    private function __construct(
        private string $value
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('Stream ID cannot be empty');
        }
    }

    /**
     * Create a new StreamId from an aggregate ID
     *
     * @param string $aggregateId The aggregate identifier
     * @return self
     */
    public static function fromAggregateId(string $aggregateId): self
    {
        return new self($aggregateId);
    }

    /**
     * Create a StreamId from a string value
     *
     * @param string $value The stream ID string
     * @return self
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Get the string value
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Get the string representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Check equality with another StreamId
     *
     * @param StreamId $other
     * @return bool
     */
    public function equals(StreamId $other): bool
    {
        return $this->value === $other->value;
    }
}
