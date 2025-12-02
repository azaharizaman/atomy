<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\Addable;
use Nexus\Common\Contracts\Comparable;
use Nexus\Common\Contracts\Convertible;
use Nexus\Common\Contracts\Divisible;
use Nexus\Common\Contracts\Formattable;
use Nexus\Common\Contracts\Multipliable;
use Nexus\Common\Contracts\SerializableVO;
use Nexus\Common\Contracts\Subtractable;
use Nexus\Common\Exceptions\InvalidValueException;

/**
 * Immutable measurement value object with unit of measurement.
 * 
 * For inventory, manufacturing, shipping measurements.
 */
final readonly class Measurement implements
    Comparable,
    Addable,
    Subtractable,
    Multipliable,
    Divisible,
    Convertible,
    SerializableVO,
    Formattable
{
    // Base unit conversion factors (to smallest unit in category)
    private const CONVERSION_FACTORS = [
        // Mass (to grams)
        'kg' => 1000,
        'g' => 1,
        'lb' => 453.592,
        'oz' => 28.3495,
        // Length (to millimeters)
        'm' => 1000,
        'cm' => 10,
        'mm' => 1,
        'ft' => 304.8,
        'in' => 25.4,
        // Volume (to milliliters)
        'L' => 1000,
        'mL' => 1,
        'gal' => 3785.41,
    ];

    /**
     * @throws InvalidValueException
     */
    public function __construct(
        private float $amount,
        private UnitOfMeasurement $unit
    ) {
        if ($amount < 0) {
            throw new InvalidValueException('Measurement amount cannot be negative');
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

    // Convertible implementation
    public function convertTo(string $toUnit): static
    {
        $targetUnit = new UnitOfMeasurement($toUnit);

        if (!$this->canConvertTo($toUnit)) {
            throw new InvalidValueException(
                "Cannot convert from {$this->unit->getValue()} to {$toUnit} - different categories"
            );
        }

        $fromUnit = $this->unit->getValue();
        
        // Convert to base unit, then to target unit
        $baseAmount = $this->amount * self::CONVERSION_FACTORS[$fromUnit];
        $convertedAmount = $baseAmount / self::CONVERSION_FACTORS[$toUnit];

        return new self($convertedAmount, $targetUnit);
    }

    public function canConvertTo(string $toUnit): bool
    {
        try {
            $targetUnit = new UnitOfMeasurement($toUnit);
            return $this->unit->canConvertTo($targetUnit)
                && isset(self::CONVERSION_FACTORS[$this->unit->getValue()])
                && isset(self::CONVERSION_FACTORS[$toUnit]);
        } catch (\Exception) {
            return false;
        }
    }

    // Comparable implementation
    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only compare with another Measurement');
        }

        // Convert to same unit for comparison
        $otherConverted = $other->canConvertTo($this->unit->getValue())
            ? $other->convertTo($this->unit->getValue())
            : $other;

        return $this->amount <=> $otherConverted->amount;
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
            throw new \InvalidArgumentException('Can only add another Measurement');
        }

        $otherConverted = $other->convertTo($this->unit->getValue());
        return new self($this->amount + $otherConverted->amount, $this->unit);
    }

    // Subtractable implementation
    public function subtract(Subtractable $other): static
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only subtract another Measurement');
        }

        $otherConverted = $other->convertTo($this->unit->getValue());
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
        return $this->format();
    }

    public static function fromArray(array $data): static
    {
        return new self(
            amount: $data['amount'],
            unit: new UnitOfMeasurement($data['unit'])
        );
    }

    // Formattable implementation
    public function format(array $options = []): string
    {
        $decimals = $options['decimals'] ?? 2;
        return number_format($this->amount, $decimals) . ' ' . $this->unit->getValue();
    }
}
