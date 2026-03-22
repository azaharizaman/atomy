<?php

declare(strict_types=1);

namespace Nexus\Outbox\ValueObjects;

use Nexus\Outbox\Internal\BoundedStringValidator;

/**
 * Tenant-scoped integration deduplication key (e.g. `EventInterface::getEventId()` from EventStream).
 */
final readonly class DedupKey
{
    public const MAX_LENGTH = 256;

    public readonly string $value;

    public function __construct(string $value)
    {
        $this->value = BoundedStringValidator::requireTrimmedNonEmpty($value, self::MAX_LENGTH, 'dedup_key');
    }
}
