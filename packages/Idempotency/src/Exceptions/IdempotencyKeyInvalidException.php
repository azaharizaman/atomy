<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Exceptions;

final class IdempotencyKeyInvalidException extends IdempotencyException
{
    public static function forField(string $field, string $reason): self
    {
        return new self(\sprintf('Invalid idempotency key for %s: %s.', $field, $reason));
    }
}
