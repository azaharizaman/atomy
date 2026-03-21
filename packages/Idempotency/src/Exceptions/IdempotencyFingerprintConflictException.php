<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Exceptions;

final class IdempotencyFingerprintConflictException extends IdempotencyException
{
    public function __construct(
        public readonly string $operationRef,
        public readonly string $clientKey,
    ) {
        parent::__construct(
            'Idempotency key reused with a different request fingerprint for this operation.'
        );
    }
}
