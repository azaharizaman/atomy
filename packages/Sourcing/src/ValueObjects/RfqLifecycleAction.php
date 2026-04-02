<?php

declare(strict_types=1);

namespace Nexus\Sourcing\ValueObjects;

use Nexus\Sourcing\Exceptions\RfqLifecyclePreconditionException;

final readonly class RfqLifecycleAction
{
    private const ALLOWED = [
        'duplicate',
        'save_draft',
        'bulk_action',
        'transition_status',
    ];

    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalized = self::normalize($value);

        if ($normalized === '') {
            throw RfqLifecyclePreconditionException::forReason('RFQ lifecycle action cannot be empty.');
        }

        if (!in_array($normalized, self::ALLOWED, true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported RFQ lifecycle action "%s".', trim($value)));
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

    private static function normalize(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[\s-]+/', '_', $normalized);

        return $normalized === null ? '' : $normalized;
    }
}
