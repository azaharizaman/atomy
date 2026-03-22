<?php

declare(strict_types=1);

return [
    'store' => env('IDEMPOTENCY_STORE', 'database'),
    
    'policy' => [
        'pending_ttl_seconds' => (int) env('IDEMPOTENCY_PENDING_TTL', 604800),
        'allow_retry_after_fail' => filter_var(env('IDEMPOTENCY_ALLOW_RETRY', true), FILTER_VALIDATE_BOOLEAN),
        'expire_completed_after_seconds' => (int) env('IDEMPOTENCY_COMPLETED_TTL', 86400),
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
