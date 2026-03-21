<?php

declare(strict_types=1);

namespace Nexus\Idempotency\ValueObjects;

use Nexus\Idempotency\Exceptions\IdempotencyKeyInvalidException;

/**
 * Opaque command result for safe replay (e.g. JSON-encoded domain DTO).
 */
final readonly class ResultEnvelope
{
    public const MAX_BYTES = 65536;

    public readonly string $payload;

    public function __construct(string $payload)
    {
        if ($payload === '') {
            throw IdempotencyKeyInvalidException::forField('result_envelope', 'must not be empty');
        }
        if (strlen($payload) > self::MAX_BYTES) {
            throw IdempotencyKeyInvalidException::forField(
                'result_envelope',
                'exceeds maximum size of ' . (string) self::MAX_BYTES . ' bytes',
            );
        }
        $this->payload = $payload;
    }

    public function equals(self $other): bool
    {
        return $this->payload === $other->payload;
    }
}
