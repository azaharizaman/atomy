<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Warehouse
    |--------------------------------------------------------------------------
    |
    | The default warehouse used when no specific warehouse is specified
    |
    */
    'default_warehouse_code' => env('WAREHOUSE_DEFAULT_CODE', 'MAIN'),

    /*
    |--------------------------------------------------------------------------
    | Picking Optimization
    |--------------------------------------------------------------------------
    |
    | Configuration for warehouse picking route optimization
    |
    */
    'picking' => [
        // Enable TSP optimization for picking routes
        'enable_optimization' => env('WAREHOUSE_PICKING_OPTIMIZATION', true),

        // Maximum bins for optimization (performance limit)
        'max_bins_for_optimization' => env('WAREHOUSE_MAX_BINS_OPTIMIZE', 100),

        // Cache optimized routes (in seconds, 0 to disable)
        'route_cache_ttl' => env('WAREHOUSE_ROUTE_CACHE_TTL', 3600),

        // Minimum bins to trigger optimization (skip for small picks)
        'min_bins_for_optimization' => env('WAREHOUSE_MIN_BINS_OPTIMIZE', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bin Location Settings
    |--------------------------------------------------------------------------
    */
    'bin_locations' => [
        // Require GPS coordinates for bin locations
        'require_coordinates' => env('WAREHOUSE_REQUIRE_GPS', false),

        // Auto-generate bin codes
        'auto_generate_codes' => env('WAREHOUSE_AUTO_BIN_CODES', true),

        // Bin code format pattern (e.g., A-01-01 for Aisle-Rack-Level)
        'code_pattern' => env('WAREHOUSE_BIN_CODE_PATTERN', '{aisle}-{rack}-{level}'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Phase 2 Features (Deferred)
    |--------------------------------------------------------------------------
    |
    | These features are planned for Phase 2 after 3-6 months of Phase 1 validation
    |
    */
    'phase2' => [
        // Work order management (deferred)
        'enable_work_orders' => false,

        // Barcode scanning integration (deferred)
        'enable_barcode_scanning' => false,

        // Real-time WebSocket updates (deferred)
        'enable_realtime_updates' => false,
    ],
];
