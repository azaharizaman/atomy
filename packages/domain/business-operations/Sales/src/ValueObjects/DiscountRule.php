<?php

declare(strict_types=1);

namespace Nexus\Sales\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexus\Sales\Enums\DiscountType;

/**
 * Discount Rule Value Object
 * 
 * Immutable representation of a discount rule with time-based validation support.
 * Used for promotional campaigns and time-sensitive pricing.
 */
final readonly class DiscountRule
{
    public function __construct(
        public DiscountType $type,
        public float $value,
        public ?float $minQuantity = null,
        public ?DateTimeImmutable $validFrom = null,
        public ?DateTimeImmutable $validUntil = null
    ) {
        $this->validate();
    }

    // All properties are public readonly; direct property access is preferred per monorepo architecture.

    /**
     * Validate discount rule data.
     */
    private function validate(): void
    {
        if ($this->value < 0) {
            throw new InvalidArgumentException('Discount value cannot be negative');
        }

        if ($this->type === DiscountType::PERCENTAGE && $this->value > 100) {
            throw new InvalidArgumentException('Percentage discount cannot exceed 100%');
        }

        if ($this->minQuantity !== null && $this->minQuantity <= 0) {
            throw new InvalidArgumentException('Minimum quantity must be positive');
        }

        if ($this->validFrom !== null && $this->validUntil !== null) {
            if ($this->validFrom > $this->validUntil) {
                throw new InvalidArgumentException('validFrom cannot be after validUntil');
            }
        }
    }

    /**
     * Check if discount is valid for a specific date.
     */
    public function isValidAt(DateTimeImmutable $date): bool
    {
        if ($this->validFrom !== null && $date < $this->validFrom) {
            return false;
        }

        if ($this->validUntil !== null && $date > $this->validUntil) {
            return false;
        }

        return true;
    }

    /**
     * Check if discount is valid for current date/time.
     */
    public function isCurrentlyValid(): bool
    {
        return $this->isValidAt(new DateTimeImmutable());
    }

    /**
     * Check if discount applies to a specific quantity.
     */
    public function appliesToQuantity(float $quantity): bool
    {
        if ($this->minQuantity === null) {
            return true;
        }

        return $quantity >= $this->minQuantity;
    }

    /**
     * Check if discount has time restrictions.
     */
    public function hasTimeRestrictions(): bool
    {
        return $this->validFrom !== null || $this->validUntil !== null;
    }

    /**
     * Convert to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'value' => $this->value,
            'min_quantity' => $this->minQuantity,
            'valid_from' => $this->validFrom?->format('Y-m-d H:i:s'),
            'valid_until' => $this->validUntil?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: DiscountType::from($data['type']),
            value: (float) $data['value'],
            minQuantity: isset($data['min_quantity']) ? (float) $data['min_quantity'] : null,
            validFrom: isset($data['valid_from']) ? new DateTimeImmutable($data['valid_from']) : null,
            validUntil: isset($data['valid_until']) ? new DateTimeImmutable($data['valid_until']) : null
        );
    }
}
