<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\Comparable;
use Nexus\Common\Contracts\SerializableVO;
use Nexus\Common\Exceptions\InvalidValueException;

/**
 * Immutable email address value object with validation.
 */
final readonly class Email implements Comparable, SerializableVO
{
    private string $value;

    /**
     * @throws InvalidValueException
     */
    public function __construct(string $value)
    {
        $trimmed = trim($value);
        
        if (!filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidValueException("Invalid email address: {$value}");
        }

        $this->value = strtolower($trimmed);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getDomain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function getLocalPart(): string
    {
        return substr($this->value, 0, strpos($this->value, '@'));
    }

    // Comparable implementation
    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only compare with another Email');
        }

        return strcmp($this->value, $other->value);
    }

    public function equals(Comparable $other): bool
    {
        return $this->compareTo($other) === 0;
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
        return [
            'value' => $this->value,
            'local_part' => $this->getLocalPart(),
            'domain' => $this->getDomain(),
        ];
    }

    public function toString(): string
    {
        return $this->value;
    }

    public static function fromArray(array $data): static
    {
        return new self($data['value']);
    }
}
