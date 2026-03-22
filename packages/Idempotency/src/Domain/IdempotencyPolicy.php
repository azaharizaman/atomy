<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Domain;

use Nexus\Idempotency\Exceptions\IdempotencyKeyInvalidException;

/**
 * TTL and retry behaviour for idempotency records.
 */
final readonly class IdempotencyPolicy
{
    /** Default time-to-live for incomplete (pending/in-progress) records: 7 days. */
    public const DEFAULT_PENDING_TTL_SECONDS = 604800;

    public readonly ?int $pendingTtlSeconds;

    public readonly bool $allowRetryAfterFail;

    public readonly ?int $expireCompletedAfterSeconds;

    public function __construct(
        ?int $pendingTtlSeconds,
        bool $allowRetryAfterFail = true,
        ?int $expireCompletedAfterSeconds = null,
    ) {
        if ($pendingTtlSeconds !== null && $pendingTtlSeconds < 0) {
            throw IdempotencyKeyInvalidException::forField('policy', 'pendingTtlSeconds must be non-negative');
        }
        if ($expireCompletedAfterSeconds !== null && $expireCompletedAfterSeconds < 0) {
            throw IdempotencyKeyInvalidException::forField('policy', 'expireCompletedAfterSeconds must be non-negative');
        }
        $this->pendingTtlSeconds = $pendingTtlSeconds;
        $this->allowRetryAfterFail = $allowRetryAfterFail;
        $this->expireCompletedAfterSeconds = $expireCompletedAfterSeconds;
    }

    public static function default(): self
    {
        return new self(
            pendingTtlSeconds: self::DEFAULT_PENDING_TTL_SECONDS,
            allowRetryAfterFail: true,
            expireCompletedAfterSeconds: null,
        );
    }
}
