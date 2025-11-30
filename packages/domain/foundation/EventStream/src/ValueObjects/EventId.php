<?php

declare(strict_types=1);

namespace Nexus\EventStream\ValueObjects;

use Symfony\Component\Uid\Ulid;

/**
 * EventId
 *
 * Value object representing a unique event identifier using ULID.
 * ULIDs are sortable by time and globally unique.
 *
 * Requirements satisfied:
 * - ARC-EVS-7009: Use Value Objects for EventId
 * - FUN-EVS-7212: Generate unique EventId (ULID) with monotonic timestamp
 *
 * @package Nexus\EventStream\ValueObjects
 */
final readonly class EventId
{
    private function __construct(
        private string $value
    ) {
        // Validate ULID format (26 characters, alphanumeric)
        if (!Ulid::isValid($value)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid ULID format: "%s". Expected 26 alphanumeric characters.',
                $value
            ));
        }
    }

    /**
     * Create a new EventId with a generated ULID
     *
     * @return self
     */
    public static function generate(): self
    {
        return new self((string) new Ulid());
    }

    /**
     * Create an EventId from an existing ULID string
     *
     * @param string $value The ULID string
     * @return self
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Get the ULID string value
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
     * Check equality with another EventId
     *
     * @param EventId $other
     * @return bool
     */
    public function equals(EventId $other): bool
    {
        return $this->value === $other->value;
    }
}
