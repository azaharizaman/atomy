<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Exceptions;

final class IdempotencyRecordExpiredException extends IdempotencyException
{
    public function __construct(
        public readonly string $operationRef,
        public readonly string $clientKey,
        string $message = 'Idempotency record has expired for this operation.',
    ) {
        parent::__construct($message);
    }
}
