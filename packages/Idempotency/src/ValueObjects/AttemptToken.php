<?php

declare(strict_types=1);

namespace Nexus\Idempotency\ValueObjects;

use Nexus\Idempotency\Internal\BoundedStringValidator;

/**
 * Opaque token bound to a single attempt after a successful FirstExecution reservation.
 */
final readonly class AttemptToken
{
    public const MAX_LENGTH = 128;

    public readonly string $value;

    public function __construct(string $value)
    {
        $this->value = BoundedStringValidator::requireTrimmedNonEmpty($value, self::MAX_LENGTH, 'attempt_token');
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
