<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\Comparable;
use Nexus\Common\Contracts\Multipliable;
use Nexus\Common\Contracts\SerializableVO;
use Nexus\Common\Exceptions\InvalidValueException;

/**
 * Immutable tax rate value object.
 * 
 * Represents tax rate with percentage, type, and jurisdiction.
 */
final readonly class TaxRate implements Comparable, Multipliable, SerializableVO
{
    /**
     * @param Percentage $rate Tax rate as percentage
     * @param string $taxType Type of tax (e.g., 'VAT', 'GST', 'Sales Tax')
     * @param string $jurisdiction Jurisdiction code (e.g., 'MY', 'US-CA')
     * @param \DateTimeImmutable $effectiveFrom When this rate becomes effective
     * @param \DateTimeImmutable|null $effectiveTo When this rate expires (null = indefinite)
     * @throws InvalidValueException
     */
    public function __construct(
        private Percentage $rate,
        private string $taxType,
        private string $jurisdiction,
        private \DateTimeImmutable $effectiveFrom,
        private ?\DateTimeImmutable $effectiveTo = null
    ) {
        if (empty(trim($taxType))) {
            throw new InvalidValueException('Tax type is required');
        }

        if (empty(trim($jurisdiction))) {
            throw new InvalidValueException('Jurisdiction is required');
        }

        if ($effectiveTo !== null && $effectiveTo < $effectiveFrom) {
            throw new InvalidValueException('Effective to date must be after effective from date');
        }
    }

    public function getRate(): Percentage
    {
        return $this->rate;
    }

    public function getTaxType(): string
    {
        return $this->taxType;
    }

    public function getJurisdiction(): string
    {
        return $this->jurisdiction;
    }

    public function getEffectiveFrom(): \DateTimeImmutable
    {
        return $this->effectiveFrom;
    }

    public function getEffectiveTo(): ?\DateTimeImmutable
    {
        return $this->effectiveTo;
    }

    public function isEffectiveOn(\DateTimeImmutable $date): bool
    {
        return $date >= $this->effectiveFrom
            && ($this->effectiveTo === null || $date <= $this->effectiveTo);
    }

    public function calculateTax(float $amount): float
    {
        return $this->rate->of($amount);
    }

    // Comparable implementation
    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only compare with another TaxRate');
        }

        // Compare by rate value
        return $this->rate->compareTo($other->rate);
    }

    public function equals(Comparable $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->rate->equals($other->rate)
            && $this->taxType === $other->taxType
            && $this->jurisdiction === $other->jurisdiction;
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
            rate: $this->rate->multiply($multiplier),
            taxType: $this->taxType,
            jurisdiction: $this->jurisdiction,
            effectiveFrom: $this->effectiveFrom,
            effectiveTo: $this->effectiveTo
        );
    }

    // SerializableVO implementation
    public function toArray(): array
    {
        return [
            'rate' => $this->rate->getValue(),
            'tax_type' => $this->taxType,
            'jurisdiction' => $this->jurisdiction,
            'effective_from' => $this->effectiveFrom->format('Y-m-d'),
            'effective_to' => $this->effectiveTo?->format('Y-m-d'),
        ];
    }

    public function toString(): string
    {
        return "{$this->taxType} {$this->rate->format()} ({$this->jurisdiction})";
    }

    public static function fromArray(array $data): static
    {
        return new self(
            rate: new Percentage($data['rate']),
            taxType: $data['tax_type'],
            jurisdiction: $data['jurisdiction'],
            effectiveFrom: new \DateTimeImmutable($data['effective_from']),
            effectiveTo: isset($data['effective_to']) ? new \DateTimeImmutable($data['effective_to']) : null
        );
    }
}
