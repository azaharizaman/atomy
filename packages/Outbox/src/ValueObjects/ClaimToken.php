<?php

declare(strict_types=1);

namespace Nexus\Outbox\ValueObjects;

use Nexus\Outbox\Internal\BoundedStringValidator;

/**
 * Opaque token bound to a single claim lease between {@see OutboxRecordStatus::Sending} and completion.
 */
final readonly class ClaimToken
{
    public const MAX_LENGTH = 128;

    public readonly string $value;

    public function __construct(string $value)
    {
        $this->value = BoundedStringValidator::requireTrimmedNonEmpty($value, self::MAX_LENGTH, 'claim_token');
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
