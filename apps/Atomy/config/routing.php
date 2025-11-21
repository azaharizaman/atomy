<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Route Cache TTL (Minutes)
    |--------------------------------------------------------------------------
    |
    | How long to cache optimized routes in minutes.
    | Routes with dynamic constraints (time windows) should use shorter TTL.
    |
    */
    'cache_ttl_minutes' => env('ROUTING_CACHE_TTL_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Offline Mode
    |--------------------------------------------------------------------------
    |
    | Enable offline route optimization using cached routes only.
    | Useful for mobile applications with intermittent connectivity.
    |
    */
    'offline_mode' => env('ROUTING_OFFLINE_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Cache Size Limits
    |--------------------------------------------------------------------------
    |
    | Maximum cache size in megabytes before automatic pruning.
    | Set to 0 to disable automatic pruning.
    |
    */
    'max_cache_size_mb' => env('ROUTING_MAX_CACHE_SIZE_MB', 100),

    /*
    |--------------------------------------------------------------------------
    | TSP Optimization Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for Traveling Salesman Problem optimizer.
    |
    */
    'tsp' => [
        // Maximum number of stops before warning
        'max_stops_recommended' => 50,
        
        // Number of 2-opt improvement iterations
        '2opt_iterations' => 100,
        
        // Enable/disable nearest neighbor heuristic
        'use_nearest_neighbor' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | VRP Optimization Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for Vehicle Routing Problem optimizer.
    |
    */
    'vrp' => [
        // Maximum number of stops before warning
        'max_stops_recommended' => 100,
        
        // Maximum number of vehicles
        'max_vehicles' => 10,
        
        // Default vehicle capacity (if not specified)
        'default_vehicle_capacity' => 1000.0,
        
        // Enable OR-Tools Docker integration (optional)
        'use_ortools' => env('ROUTING_USE_ORTOOLS', false),
        
        // OR-Tools service URL (if enabled)
        'ortools_url' => env('ORTOOLS_URL', 'http://ortools:8080'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Constraint Validation
    |--------------------------------------------------------------------------
    |
    | Enable/disable specific constraint checks.
    |
    */
    'constraints' => [
        'validate_time_windows' => true,
        'validate_capacity' => true,
        'validate_duration' => true,
        'strict_mode' => env('ROUTING_STRICT_MODE', false), // Throw exception on violation
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable optimization execution logging to database.
    | Useful for performance analysis and monitoring.
    |
    */
    'log_optimizations' => env('ROUTING_LOG_OPTIMIZATIONS', true),
];
