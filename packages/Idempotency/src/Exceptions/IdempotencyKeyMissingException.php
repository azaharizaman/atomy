<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Exceptions;

final class IdempotencyKeyMissingException extends IdempotencyException
{
    public static function forField(string $field): self
    {
        return new self(\sprintf('Idempotency key is missing: %s.', $field));
    }
}
