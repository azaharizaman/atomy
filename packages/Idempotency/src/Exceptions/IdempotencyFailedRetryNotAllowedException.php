<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Exceptions;

/**
 * Thrown when begin() is attempted for a key that is in Failed state and policy disallows retry.
 */
final class IdempotencyFailedRetryNotAllowedException extends IdempotencyException
{
    public static function create(): self
    {
        return new self(
            'Idempotency key is in a failed state and retry is not allowed by policy.'
        );
    }
}
