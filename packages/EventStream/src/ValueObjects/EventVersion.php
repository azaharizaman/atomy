<?php

declare(strict_types=1);

namespace Nexus\EventStream\ValueObjects;

/**
 * EventVersion
 *
 * Value object representing the version of an event in a stream.
 * Used for optimistic concurrency control.
 *
 * Requirements satisfied:
 * - ARC-EVS-7009: Use Value Objects for EventVersion
 *
 * @package Nexus\EventStream\ValueObjects
 */
final readonly class EventVersion
{
    private function __construct(
        private int $value
    ) {
        if ($value < 0) {
            throw new \InvalidArgumentException('Event version must be non-negative');
        }
    }

    /**
     * Create an EventVersion from an integer
     *
     * @param int $value The version number
     * @return self
     */
    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    /**
     * Create the initial version (0)
     *
     * @return self
     */
    public static function initial(): self
    {
        return new self(0);
    }

    /**
     * Create the first version (alias for initial)
     *
     * @return self
     */
    public static function first(): self
    {
        return new self(1);
    }

    /**
     * Get the next version
     *
     * @return self
     */
    public function next(): self
    {
        return new self($this->value + 1);
    }

    /**
     * Get the integer value
     *
     * @return int
     */
    public function toInt(): int
    {
        return $this->value;
    }

    /**
     * Check if this version is after another version
     *
     * @param EventVersion $other
     * @return bool
     */
    public function isAfter(EventVersion $other): bool
    {
        return $this->value > $other->value;
    }

    /**
     * Check if this version is greater than another version
     *
     * @param EventVersion $other
     * @return bool
     */
    public function isGreaterThan(EventVersion $other): bool
    {
        return $this->value > $other->value;
    }

    /**
     * Check if this version is less than another version
     *
     * @param EventVersion $other
     * @return bool
     */
    public function isLessThan(EventVersion $other): bool
    {
        return $this->value < $other->value;
    }

    /**
     * Check equality with another EventVersion
     *
     * @param EventVersion $other
     * @return bool
     */
    public function equals(EventVersion $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Convert to string
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }
}
