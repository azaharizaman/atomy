<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\Addable;
use Nexus\Common\Contracts\Comparable;
use Nexus\Common\Contracts\Divisible;
use Nexus\Common\Contracts\Formattable;
use Nexus\Common\Contracts\Multipliable;
use Nexus\Common\Contracts\SerializableVO;
use Nexus\Common\Contracts\Subtractable;
use Nexus\Common\Exceptions\InvalidValueException;

/**
 * Immutable percentage value object.
 * 
 * Stores percentage as float (0-100 range). Used for taxes, discounts, variance analysis.
 */
final readonly class Percentage implements
    Comparable,
    Addable,
    Subtractable,
    Multipliable,
    Divisible,
    SerializableVO,
    Formattable
{
    private float $value;

    /**
     * @param float $value Percentage value (0-100)
     * @throws InvalidValueException
     */
    public function __construct(float $value)
    {
        if ($value < 0 || $value > 100) {
            throw new InvalidValueException("Percentage must be between 0 and 100, got: {$value}");
        }

        $this->value = $value;
    }

    /**
     * Create from decimal value (0.0-1.0).
     */
    public static function fromDecimal(float $decimal): self
    {
        return new self($decimal * 100);
    }

    /**
     * Get percentage value (0-100).
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * Get as decimal (0.0-1.0).
     */
    public function asDecimal(): float
    {
        return $this->value / 100;
    }

    /**
     * Apply percentage to a value.
     */
    public function of(float $amount): float
    {
        return $amount * $this->asDecimal();
    }

    // Comparable implementation
    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only compare with another Percentage');
        }

        return $this->value <=> $other->value;
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

    // Addable implementation
    public function add(Addable $other): static
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only add another Percentage');
        }

        return new self($this->value + $other->value);
    }

    // Subtractable implementation
    public function subtract(Subtractable $other): static
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only subtract another Percentage');
        }

        return new self($this->value - $other->value);
    }

    // Multipliable implementation
    public function multiply(float|int $multiplier): static
    {
        return new self($this->value * $multiplier);
    }

    // Divisible implementation
    public function divide(float|int $divisor): static
    {
        if ($divisor == 0) {
            throw new InvalidValueException('Cannot divide by zero');
        }

        return new self($this->value / $divisor);
    }

    // SerializableVO implementation
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'decimal' => $this->asDecimal(),
        ];
    }

    public function toString(): string
    {
        return $this->format();
    }

    public static function fromArray(array $data): static
    {
        return new self($data['value']);
    }

    // Formattable implementation
    public function format(array $options = []): string
    {
        $decimals = $options['decimals'] ?? 2;
        return number_format($this->value, $decimals) . '%';
    }
}
