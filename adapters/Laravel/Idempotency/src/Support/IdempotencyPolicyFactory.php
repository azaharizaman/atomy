<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Support;

use Nexus\Idempotency\Domain\IdempotencyPolicy;

final readonly class IdempotencyPolicyFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public function make(array $config): IdempotencyPolicy
    {
        $default = IdempotencyPolicy::default();
        $pending = $config['pending_ttl_seconds'] ?? $default->pendingTtlSeconds;
        $completed = $config['expire_completed_after_seconds'] ?? $default->expireCompletedAfterSeconds;

        return new IdempotencyPolicy(
            pendingTtlSeconds: is_int($pending) ? $pending : (int) $pending,
            allowRetryAfterFail: array_key_exists('allow_retry_after_fail', $config)
                ? (bool) $config['allow_retry_after_fail']
                : $default->allowRetryAfterFail,
            expireCompletedAfterSeconds: $completed === null ? null : (is_int($completed) ? $completed : (int) $completed),
        );
    }
}
