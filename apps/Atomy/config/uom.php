<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Unit of Measurement Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the UoM package implementation in Atomy.
    |
    */

    /**
     * Default locale for quantity formatting.
     */
    'default_locale' => 'en_US',

    /**
     * Default precision for conversion calculations.
     */
    'calculation_precision' => 15,

    /**
     * Cache conversion paths for performance.
     */
    'cache_conversions' => true,

    /**
     * Cache duration in seconds (default 1 hour).
     */
    'cache_duration' => 3600,

    /**
     * Enable tenant isolation for units.
     */
    'tenant_isolation' => false,

    /**
     * Enable audit logging for unit operations.
     */
    'audit_logging' => true,

    /**
     * Predefined dimensions to seed.
     */
    'seed_dimensions' => [
        'mass' => ['name' => 'Mass', 'base_unit' => 'kg', 'allows_offset' => false],
        'length' => ['name' => 'Length', 'base_unit' => 'm', 'allows_offset' => false],
        'time' => ['name' => 'Time', 'base_unit' => 's', 'allows_offset' => false],
        'temperature' => ['name' => 'Temperature', 'base_unit' => 'c', 'allows_offset' => true],
        'volume' => ['name' => 'Volume', 'base_unit' => 'l', 'allows_offset' => false],
        'area' => ['name' => 'Area', 'base_unit' => 'm2', 'allows_offset' => false],
    ],

    /**
     * Predefined unit systems.
     */
    'seed_systems' => [
        'metric' => 'Metric System (SI)',
        'imperial' => 'Imperial System',
        'us' => 'US Customary Units',
    ],
];
