<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Exceptions;

/**
 * Defensive: store returned a record whose tenant does not match the request (buggy adapter).
 * Message is generic; do not add tenant identifiers to exception payloads.
 */
final class IdempotencyTenantMismatchException extends IdempotencyException
{
    public static function create(): self
    {
        return new self('Tenant mismatch for idempotency record.');
    }
}
