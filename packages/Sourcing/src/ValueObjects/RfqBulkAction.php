<?php

declare(strict_types=1);

namespace Nexus\Sourcing\ValueObjects;

use Nexus\Sourcing\Exceptions\UnsupportedRfqBulkActionException;

final readonly class RfqBulkAction
{
    private const ALLOWED = [
        'close',
        'cancel',
    ];

    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        if (!in_array($normalized, self::ALLOWED, true)) {
            throw UnsupportedRfqBulkActionException::fromAction($value, self::ALLOWED);
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
