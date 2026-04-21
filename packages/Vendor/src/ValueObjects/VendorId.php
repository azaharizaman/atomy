<?php

declare(strict_types=1);

namespace Nexus\Vendor\ValueObjects;

final readonly class VendorId
{
    private const ULID_LENGTH = 26;
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    private string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));

        if ($normalized === '') {
            throw new \InvalidArgumentException('Vendor ID cannot be empty.');
        }

        if (strlen($normalized) !== self::ULID_LENGTH || !preg_match(self::ULID_PATTERN, $normalized)) {
            throw new \InvalidArgumentException(sprintf('Vendor ID must be a valid ULID, got: %s', $value));
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
