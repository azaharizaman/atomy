<?php

declare(strict_types=1);

use Nexus\Idempotency\Domain\IdempotencyPolicy;

return [
    'policy' => [
        'pending_ttl_seconds' => (int) env('IDEMPOTENCY_PENDING_TTL', IdempotencyPolicy::DEFAULT_PENDING_TTL_SECONDS),
        'allow_retry_after_fail' => filter_var(env('IDEMPOTENCY_ALLOW_RETRY', true), FILTER_VALIDATE_BOOL),
        'expire_completed_after_seconds' => env('IDEMPOTENCY_COMPLETED_TTL') !== null
            ? (int) env('IDEMPOTENCY_COMPLETED_TTL')
            : null,
    ],
];
