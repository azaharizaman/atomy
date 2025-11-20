<?php

declare(strict_types=1);

namespace Nexus\Payable\ValueObjects;

/**
 * Vendor bill number value object.
 */
final readonly class VendorBillNumber
{
    public function __construct(
        private string $value
    ) {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('Vendor bill number cannot be empty.');
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
