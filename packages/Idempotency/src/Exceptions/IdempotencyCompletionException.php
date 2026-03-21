<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Exceptions;

final class IdempotencyCompletionException extends IdempotencyException
{
    public static function wrongState(string $message): self
    {
        return new self($message);
    }
}
