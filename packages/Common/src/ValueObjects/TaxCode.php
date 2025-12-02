<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\Comparable;
use Nexus\Common\Contracts\Multipliable;
use Nexus\Common\Contracts\SerializableVO;
use Nexus\Common\Exceptions\InvalidValueException;

/**
 * Immutable tax code value object.
 * 
 * Represents a tax code with associated rate and description.
 */
final readonly class TaxCode implements Comparable, Multipliable, SerializableVO
{
    /**
     * @param string $code Tax code (e.g., 'TX', 'SR', 'ZR')
     * @param string $description Human-readable description
     * @param Percentage $rate Tax rate percentage
     * @param bool $isActive Whether this tax code is currently active
     * @throws InvalidValueException
     */
    public function __construct(
        private string $code,
        private string $description,
        private Percentage $rate,
        private bool $isActive = true
    ) {
        if (empty(trim($code))) {
            throw new InvalidValueException('Tax code is required');
        }

        if (empty(trim($description))) {
            throw new InvalidValueException('Tax description is required');
        }
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRate(): Percentage
    {
        return $this->rate;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function calculateTax(float $amount): float
    {
        return $this->rate->of($amount);
    }

    // Comparable implementation
    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only compare with another TaxCode');
        }

        // Compare by rate
        return $this->rate->compareTo($other->rate);
    }

    public function equals(Comparable $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->code === $other->code;
    }

    public function greaterThan(Comparable $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function lessThan(Comparable $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    // Multipliable implementation
    public function multiply(float|int $multiplier): static
    {
        return new self(
            code: $this->code,
            description: $this->description,
            rate: $this->rate->multiply($multiplier),
            isActive: $this->isActive
        );
    }

    // SerializableVO implementation
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'description' => $this->description,
            'rate' => $this->rate->getValue(),
            'is_active' => $this->isActive,
        ];
    }

    public function toString(): string
    {
        return "{$this->code}: {$this->description} ({$this->rate->format()})";
    }

    public static function fromArray(array $data): static
    {
        return new self(
            code: $data['code'],
            description: $data['description'],
            rate: new Percentage($data['rate']),
            isActive: $data['is_active'] ?? true
        );
    }
}
