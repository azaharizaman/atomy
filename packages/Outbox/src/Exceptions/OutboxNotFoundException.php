<?php

declare(strict_types=1);

namespace Nexus\Outbox\Exceptions;

use Nexus\Outbox\ValueObjects\OutboxMessageId;

final class OutboxNotFoundException extends OutboxException
{
    public static function forMessageId(OutboxMessageId $id): self
    {
        return new self('Outbox message not found: ' . $id->value);
    }
}
