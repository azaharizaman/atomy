<?php

declare(strict_types=1);

namespace Nexus\Product\ValueObjects;

use JsonSerializable;
use Nexus\Product\Exceptions\InvalidProductDataException;

/**
 * SKU (Stock Keeping Unit) Value Object
 *
 * Immutable, validated unique identifier for product variants.
 * Must be unique within tenant scope.
 */
final readonly class Sku implements JsonSerializable
{
    /**
     * @param string $value The SKU identifier
     * @throws InvalidProductDataException
     */
    public function __construct(
        public string $value
    ) {
        $this->validate();
    }

    /**
     * Validate SKU format
     *
     * @throws InvalidProductDataException
     */
    private function validate(): void
    {
        $trimmed = trim($this->value);
        
        if ($trimmed === '') {
            throw InvalidProductDataException::emptySkuValue();
        }

        if (strlen($trimmed) > 100) {
            throw InvalidProductDataException::skuTooLong($trimmed);
        }

        // SKU should not contain control characters or excessive whitespace
        if (preg_match('/[\x00-\x1F\x7F]/', $trimmed)) {
            throw InvalidProductDataException::skuContainsInvalidCharacters($trimmed);
        }
    }

    /**
     * Get the SKU value
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Check if two SKUs are equal
     *
     * @param self $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Convert to array representation
     *
     * @return array{value: string}
     */
    public function toArray(): array
    {
        return ['value' => $this->value];
    }

    /**
     * Create from array representation
     *
     * @param array{value: string} $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self($data['value']);
    }

    /**
     * JSON serialization
     *
     * @return array{value: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
