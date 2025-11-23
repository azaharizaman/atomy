<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Event Sourcing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Nexus EventStream package.
    | Event Sourcing is RESERVED for critical domains: Finance (GL), Inventory.
    |
    */

    /**
     * Enable event sourcing globally
     * When false, all event store operations will be no-ops
     */
    'enabled' => env('EVENTSTREAM_ENABLED', true),

    /**
     * Snapshot Configuration
     */
    'snapshot_threshold' => env('EVENTSTREAM_SNAPSHOT_THRESHOLD', 100),

    /**
     * Event Archive Configuration
     */
    'archive' => [
        'enabled' => env('EVENTSTREAM_ARCHIVE_ENABLED', true),
        'retention_days' => env('EVENTSTREAM_ARCHIVE_RETENTION_DAYS', 365),
        'storage_disk' => env('EVENTSTREAM_ARCHIVE_DISK', 's3'),
    ],

    /**
     * Projection Configuration
     */
    'projections' => [
        'enabled' => env('EVENTSTREAM_PROJECTIONS_ENABLED', true),
        'lag_threshold_seconds' => env('EVENTSTREAM_PROJECTION_LAG_THRESHOLD', 60),
    ],

    /**
     * Performance Settings
     */
    'performance' => [
        'batch_size' => env('EVENTSTREAM_BATCH_SIZE', 1000),
        'cache_snapshots' => env('EVENTSTREAM_CACHE_SNAPSHOTS', true),
        'cache_ttl_seconds' => env('EVENTSTREAM_CACHE_TTL', 300), // 5 minutes
    ],

    /**
     * Event Store Backend
     * Supported: 'sql', 'mongodb', 'eventstoredb'
     */
    'backend' => env('EVENTSTREAM_BACKEND', 'sql'),

    /**
     * Security Settings
     */
    'security' => [
        'encrypt_payloads' => env('EVENTSTREAM_ENCRYPT_PAYLOADS', true),
        'hash_algorithm' => 'sha256',
    ],

    /**
     * Critical Domains (where Event Sourcing is used)
     */
    'critical_domains' => [
        'finance' => true,      // GL events (AccountCreditedEvent, AccountDebitedEvent)
        'inventory' => true,    // Stock events (StockReservedEvent, StockShippedEvent)
        'payable' => env('EVENTSTREAM_PAYABLE_ENABLED', false),   // Large enterprise only
        'receivable' => env('EVENTSTREAM_RECEIVABLE_ENABLED', false), // Large enterprise only
    ],

    /**
     * Metrics Configuration (Prometheus)
     */
    'metrics' => [
        // Storage backend for metrics (redis or memory)
        'storage' => env('EVENTSTREAM_METRICS_STORAGE', 'redis'),

        // Redis connection for metrics (shared across workers)
        'redis' => [
            'host' => env('EVENTSTREAM_METRICS_REDIS_HOST', '127.0.0.1'),
            'port' => env('EVENTSTREAM_METRICS_REDIS_PORT', 63971),
            'database' => env('EVENTSTREAM_METRICS_REDIS_DATABASE', 0),
            'timeout' => 2.0,
        ],

        // Histogram buckets (milliseconds) for event append/read operations
        'event_operation_buckets' => [1, 5, 10, 25, 50, 100, 250, 500, 1000, 2500, 5000],

        // Histogram buckets (milliseconds) for projection processing
        'projection_processing_buckets' => [1, 10, 50, 100, 500, 1000, 2500, 5000, 10000, 30000],
    ],
];
