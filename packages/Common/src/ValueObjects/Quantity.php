<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\Addable;
use Nexus\Common\Contracts\Comparable;
use Nexus\Common\Contracts\Convertible;
use Nexus\Common\Contracts\Divisible;
use Nexus\Common\Contracts\Multipliable;
use Nexus\Common\Contracts\SerializableVO;
use Nexus\Common\Contracts\Subtractable;
use Nexus\Common\Exceptions\InvalidValueException;

/**
 * Immutable quantity value object for discrete items with unit conversion.
 * 
 * Similar to Measurement but optimized for inventory quantities.
 */
final readonly class Quantity implements
    Comparable,
    Addable,
    Subtractable,
    Multipliable,
    Divisible,
    Convertible,
    SerializableVO
{
    /**
     * @throws InvalidValueException
     */
    public function __construct(
        private float $amount,
        private UnitOfMeasurement $unit
    ) {
        if ($amount < 0) {
            throw new InvalidValueException('Quantity cannot be negative');
        }
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getUnit(): UnitOfMeasurement
    {
        return $this->unit;
    }

    public function isZero(): bool
    {
        return $this->amount === 0.0;
    }

    // Convertible implementation
    public function convertTo(string $toUnit): static
    {
        // For now, basic conversion - could be enhanced with custom conversion rules
        $measurement = new Measurement($this->amount, $this->unit);
        $converted = $measurement->convertTo($toUnit);

        return new self($converted->getAmount(), $converted->getUnit());
    }

    public function canConvertTo(string $toUnit): bool
    {
        $measurement = new Measurement($this->amount, $this->unit);
        return $measurement->canConvertTo($toUnit);
    }

    // Comparable implementation
    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only compare with another Quantity');
        }

        // Try to convert for comparison
        if ($other->canConvertTo($this->unit->getValue())) {
            $otherConverted = $other->convertTo($this->unit->getValue());
            return $this->amount <=> $otherConverted->amount;
        }

        // If cannot convert, compare by amount only
        return $this->amount <=> $other->amount;
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
            throw new \InvalidArgumentException('Can only add another Quantity');
        }

        $otherConverted = $other->canConvertTo($this->unit->getValue())
            ? $other->convertTo($this->unit->getValue())
            : throw new InvalidValueException('Cannot add quantities with incompatible units');

        return new self($this->amount + $otherConverted->amount, $this->unit);
    }

    // Subtractable implementation
    public function subtract(Subtractable $other): static
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only subtract another Quantity');
        }

        $otherConverted = $other->canConvertTo($this->unit->getValue())
            ? $other->convertTo($this->unit->getValue())
            : throw new InvalidValueException('Cannot subtract quantities with incompatible units');

        return new self($this->amount - $otherConverted->amount, $this->unit);
    }

    // Multipliable implementation
    public function multiply(float|int $multiplier): static
    {
        return new self($this->amount * $multiplier, $this->unit);
    }

    // Divisible implementation
    public function divide(float|int $divisor): static
    {
        if ($divisor == 0) {
            throw new InvalidValueException('Cannot divide by zero');
        }

        return new self($this->amount / $divisor, $this->unit);
    }

    // SerializableVO implementation
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'unit' => $this->unit->getValue(),
        ];
    }

    public function toString(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->unit->getValue();
    }

    public static function fromArray(array $data): static
    {
        return new self(
            amount: $data['amount'],
            unit: new UnitOfMeasurement($data['unit'])
        );
    }
}
