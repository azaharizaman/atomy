<?php

declare(strict_types=1);

namespace Nexus\Inventory\ValueObjects;

use Nexus\Inventory\Exceptions\InventoryException;

/**
 * Serial Number value object
 * 
 * Represents a unique serial identifier for individual items
 */
final readonly class SerialNumber
{
    public function __construct(
        private string $value
    ) {
        if (empty(trim($value))) {
            throw new InventoryException('Serial number cannot be empty');
        }
        
        if (strlen($value) > 100) {
            throw new InventoryException('Serial number cannot exceed 100 characters');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
