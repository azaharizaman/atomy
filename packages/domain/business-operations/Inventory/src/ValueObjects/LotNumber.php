<?php

declare(strict_types=1);

namespace Nexus\Inventory\ValueObjects;

use Nexus\Inventory\Exceptions\InventoryException;

/**
 * Lot Number value object
 * 
 * Represents a unique lot identifier with optional expiry date
 */
final readonly class LotNumber
{
    public function __construct(
        private string $value,
        private ?\DateTimeImmutable $expiryDate = null
    ) {
        if (empty(trim($value))) {
            throw new InventoryException('Lot number cannot be empty');
        }
        
        if (strlen($value) > 50) {
            throw new InventoryException('Lot number cannot exceed 50 characters');
        }
    }

    public static function fromString(string $value, ?\DateTimeImmutable $expiryDate = null): self
    {
        return new self($value, $expiryDate);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getExpiryDate(): ?\DateTimeImmutable
    {
        return $this->expiryDate;
    }

    public function isExpired(\DateTimeImmutable $asOf = null): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        $asOf = $asOf ?? new \DateTimeImmutable();
        
        return $this->expiryDate < $asOf;
    }

    public function daysUntilExpiry(\DateTimeImmutable $asOf = null): ?int
    {
        if ($this->expiryDate === null) {
            return null;
        }

        $asOf = $asOf ?? new \DateTimeImmutable();
        
        return (int) $asOf->diff($this->expiryDate)->format('%r%a');
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
