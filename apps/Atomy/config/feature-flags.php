<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Nexus Feature Flags package.
    |
    */

    /**
     * Cache store for feature flag definitions.
     *
     * Options: 'redis', 'memcached', 'file', 'array'
     *
     * Recommended: 'redis' for production (shared across workers)
     */
    'cache_store' => env('FEATURE_FLAGS_CACHE_STORE', 'redis'),

    /**
     * Cache TTL (Time To Live) in seconds.
     *
     * Balance between performance and freshness:
     * - Lower TTL (60-120s): More frequent database queries, fresher data
     * - Higher TTL (300-600s): Better performance, potential staleness
     *
     * Default: 300 seconds (5 minutes)
     */
    'cache_ttl' => env('FEATURE_FLAGS_CACHE_TTL', 300),

    /**
     * Default behavior for unknown flags.
     *
     * When a flag is not found in the database:
     * - false: Fail-closed (secure default, recommended)
     * - true: Fail-open (permissive, use with caution)
     *
     * This can be overridden per-call via the `defaultIfNotFound` parameter.
     */
    'default_if_not_found' => env('FEATURE_FLAGS_DEFAULT_IF_NOT_FOUND', false),

    /**
     * Enable metrics tracking via Nexus\Monitoring.
     *
     * If enabled and Nexus\Monitoring is installed, the system will track:
     * - flag_evaluation_duration_ms (timing)
     * - flag_evaluation_total (counter)
     * - flag_evaluation_errors_total (counter)
     * - bulk_evaluation_duration_ms (timing)
     *
     * Default: true (if Nexus\Monitoring is available)
     */
    'enable_monitoring' => env('FEATURE_FLAGS_ENABLE_MONITORING', true),
];
