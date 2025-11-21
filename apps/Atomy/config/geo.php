<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Google Maps API Key
    |--------------------------------------------------------------------------
    |
    | Your Google Maps API key for geocoding services.
    | Sign up at: https://console.cloud.google.com/apis/credentials
    | Estimated cost: $5 per 1,000 requests (above free tier of 28,500/month)
    |
    */
    'google_maps_api_key' => env('GOOGLE_MAPS_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Nominatim User Agent
    |--------------------------------------------------------------------------
    |
    | User-Agent string for Nominatim (OpenStreetMap) geocoding service.
    | Required by Nominatim usage policy.
    |
    */
    'nominatim_user_agent' => env('NOMINATIM_USER_AGENT', 'Nexus/1.0'),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (Days)
    |--------------------------------------------------------------------------
    |
    | How long to cache geocoding results in days.
    | Recommended: 90 days for cost optimization (>80% hit rate target)
    |
    */
    'cache_ttl_days' => env('GEO_CACHE_TTL_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Polygon Simplification
    |--------------------------------------------------------------------------
    |
    | Douglas-Peucker algorithm settings for polygon simplification.
    |
    */
    'polygon_simplification' => [
        'default_tolerance_meters' => 10.0,
        'max_vertices' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Travel Time Estimation
    |--------------------------------------------------------------------------
    |
    | Default average speeds for travel time estimation.
    | Speeds are in kilometers per hour (km/h).
    |
    */
    'travel_speeds' => [
        'highway' => 90.0,
        'urban' => 40.0,
        'rural' => 60.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Configuration
    |--------------------------------------------------------------------------
    |
    | Circuit breaker settings for geocoding providers.
    | These should match your Connector package configuration.
    |
    */
    'circuit_breaker' => [
        'google_maps' => [
            'failure_threshold' => 5,
            'timeout_seconds' => 60,
        ],
        'nominatim' => [
            'failure_threshold' => 3,
            'timeout_seconds' => 30,
        ],
    ],
];
