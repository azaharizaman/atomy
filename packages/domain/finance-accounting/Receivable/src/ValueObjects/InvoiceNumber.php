<?php

declare(strict_types=1);

namespace Nexus\Receivable\ValueObjects;

use InvalidArgumentException;

/**
 * Invoice Number Value Object
 *
 * Represents a unique customer invoice number.
 * Immutable and validated on construction.
 */
final readonly class InvoiceNumber
{
    public function __construct(
        private string $value
    ) {
        if (empty(trim($value))) {
            throw new InvalidArgumentException('Invoice number cannot be empty');
        }

        if (strlen($value) > 100) {
            throw new InvalidArgumentException('Invoice number cannot exceed 100 characters');
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
