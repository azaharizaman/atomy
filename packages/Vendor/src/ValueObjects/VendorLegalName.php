<?php

declare(strict_types=1);

namespace Nexus\Vendor\ValueObjects;

final readonly class VendorLegalName
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new \InvalidArgumentException('Vendor legal name cannot be empty.');
        }

        $this->value = $normalized;
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
}
