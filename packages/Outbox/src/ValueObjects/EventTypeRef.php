<?php

declare(strict_types=1);

namespace Nexus\Outbox\ValueObjects;

use Nexus\Outbox\Internal\BoundedStringValidator;

/**
 * Logical event type (e.g. FQCN from `Nexus\EventStream\Contracts\EventInterface::getEventType()`).
 */
final readonly class EventTypeRef
{
    public const MAX_LENGTH = 512;

    public readonly string $value;

    public function __construct(string $value)
    {
        $this->value = BoundedStringValidator::requireTrimmedNonEmpty($value, self::MAX_LENGTH, 'event_type');
    }
}
