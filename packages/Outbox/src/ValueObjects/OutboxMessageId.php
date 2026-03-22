<?php

declare(strict_types=1);

namespace Nexus\Outbox\ValueObjects;

use Nexus\Outbox\Internal\BoundedStringValidator;

final readonly class OutboxMessageId
{
    public const MAX_LENGTH = 128;

    public readonly string $value;

    public function __construct(string $value)
    {
        $this->value = BoundedStringValidator::requireTrimmedNonEmpty($value, self::MAX_LENGTH, 'outbox_message_id');
    }

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(16)));
    }
}
