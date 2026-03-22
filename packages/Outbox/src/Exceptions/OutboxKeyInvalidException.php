<?php

declare(strict_types=1);

namespace Nexus\Outbox\Exceptions;

final class OutboxKeyInvalidException extends OutboxException
{
    public static function forField(string $fieldName, string $reason): self
    {
        return new self(\sprintf('%s: %s', $fieldName, $reason));
    }
}
