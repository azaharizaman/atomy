<?php

declare(strict_types=1);

namespace Nexus\EventStream\ValueObjects;

/**
 * AggregateId
 *
 * Value object representing a unique aggregate identifier.
 *
 * Requirements satisfied:
 * - ARC-EVS-7009: Use Value Objects for AggregateId
 *
 * @package Nexus\EventStream\ValueObjects
 */
final readonly class AggregateId
{
    private function __construct(
        private string $value
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('Aggregate ID cannot be empty');
        }
    }

    /**
     * Create an AggregateId from a string
     *
     * @param string $value The aggregate ID string
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
     * Check equality with another AggregateId
     *
     * @param AggregateId $other
     * @return bool
     */
    public function equals(AggregateId $other): bool
    {
        return $this->value === $other->value;
    }
}
