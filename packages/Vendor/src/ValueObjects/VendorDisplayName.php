<?php

declare(strict_types=1);

namespace Nexus\Vendor\ValueObjects;

use Nexus\Vendor\Internal\BoundedStringValidator;

final readonly class VendorDisplayName
{
    private const MAX_LENGTH = 255;

    private string $value;

    public function __construct(string $value)
    {
        $this->value = BoundedStringValidator::requireTrimmedNonEmpty(
            $value,
            self::MAX_LENGTH,
            'Vendor display name cannot be empty.',
            'Vendor display name exceeds maximum length.',
        );
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
