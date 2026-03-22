<?php

declare(strict_types=1);

namespace Nexus\Idempotency\ValueObjects;

use Nexus\Idempotency\Internal\BoundedStringValidator;

final readonly class RequestFingerprint
{
    public const MAX_LENGTH = 512;

    public readonly string $value;

    public function __construct(string $value)
    {
        $this->value = BoundedStringValidator::requireTrimmedNonEmpty($value, self::MAX_LENGTH, 'fingerprint');
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
