<?php

declare(strict_types=1);

namespace Nexus\Receivable\ValueObjects;

use InvalidArgumentException;

/**
 * Receipt Number Value Object
 *
 * Represents a unique payment receipt number.
 * Immutable and validated on construction.
 */
final readonly class ReceiptNumber
{
    public function __construct(
        private string $value
    ) {
        if (empty(trim($value))) {
            throw new InvalidArgumentException('Receipt number cannot be empty');
        }

        if (strlen($value) > 100) {
            throw new InvalidArgumentException('Receipt number cannot exceed 100 characters');
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
