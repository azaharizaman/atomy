<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Support;

use Illuminate\Support\Facades\Config;
use Nexus\Idempotency\Domain\IdempotencyPolicy;

final class IdempotencyPolicyFactory
{
    public static function make(): IdempotencyPolicy
    {
        $config = Config::get('nexus-idempotency.policy', []);

        return new IdempotencyPolicy(
            pendingTtlSeconds: $config['pending_ttl_seconds'] ?? IdempotencyPolicy::DEFAULT_PENDING_TTL_SECONDS,
            allowRetryAfterFail: $config['allow_retry_after_fail'] ?? true,
            expireCompletedAfterSeconds: $config['expire_completed_after_seconds'] ?? 86400,
        );
    }
}
