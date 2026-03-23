<?php

declare(strict_types=1);

use Nexus\Idempotency\Domain\IdempotencyPolicy;

return [
    'store' => env('IDEMPOTENCY_STORE', 'database'),

    'policy' => [
        'pending_ttl_seconds' => (int) env('IDEMPOTENCY_PENDING_TTL', IdempotencyPolicy::DEFAULT_PENDING_TTL_SECONDS),
        'allow_retry_after_fail' => filter_var(env('IDEMPOTENCY_ALLOW_RETRY', true), FILTER_VALIDATE_BOOL),
        'expire_completed_after_seconds' => env('IDEMPOTENCY_COMPLETED_TTL') !== null
            ? (int) env('IDEMPOTENCY_COMPLETED_TTL')
            : null,
    ],

    'redis' => [
        'connection' => env('IDEMPOTENCY_REDIS_CONNECTION', 'default'),
        'prefix' => 'nexus:idempotency:',
    ],

    'middleware' => [
        'enabled' => true,
        'header_name' => 'Idempotency-Key',
        'tenant_header' => 'X-Tenant-ID',
    ],
];
