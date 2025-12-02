<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\Comparable;
use Nexus\Common\Contracts\SerializableVO;
use Nexus\Common\Exceptions\InvalidValueException;
use Symfony\Component\Uid\Ulid;

/**
 * Base class for strongly-typed entity identifiers.
 * 
 * Uses ULID (Universally Unique Lexicographically Sortable Identifier).
 * Prevents mixing different entity types (e.g., CustomerId vs ProductId).
 */
abstract readonly class EntityId implements Comparable, SerializableVO
{
    protected string $value;

    /**
     * @throws InvalidValueException
     */
    public function __construct(string $value)
    {
        if (!Ulid::isValid($value)) {
            throw new InvalidValueException("Invalid ULID format: {$value}");
        }

        $this->value = $value;
    }

    /**
     * Generate new ID.
     */
    public static function generate(): static
    {
        return new static((string) new Ulid());
    }

    /**
     * Create from existing ULID string.
     */
    public static function fromString(string $value): static
    {
        return new static($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toUlid(): Ulid
    {
        return Ulid::fromString($this->value);
    }

    // Comparable implementation
    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof static) {
            throw new \InvalidArgumentException('Can only compare with same EntityId type');
        }

        return strcmp($this->value, $other->value);
    }

    public function equals(Comparable $other): bool
    {
        return $other instanceof static && $this->value === $other->value;
    }

    public function greaterThan(Comparable $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function lessThan(Comparable $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    // SerializableVO implementation
    public function toArray(): array
    {
        return ['value' => $this->value];
    }

    public function toString(): string
    {
        return $this->value;
    }

    public static function fromArray(array $data): static
    {
        return new static($data['value']);
    }
}
