<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Exceptions;

final class IdempotencyTenantMismatchException extends IdempotencyException
{
    public function __construct(
        public readonly string $expectedTenantId,
        public readonly string $actualTenantId,
    ) {
        parent::__construct('Tenant mismatch for idempotency record.');
    }
}
