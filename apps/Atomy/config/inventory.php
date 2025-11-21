<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | General Ledger Integration
    |--------------------------------------------------------------------------
    |
    | Configure automatic GL posting for inventory transactions
    |
    */
    'gl_integration_enabled' => env('INVENTORY_GL_INTEGRATION_ENABLED', true),

    'gl' => [
        'asset_account' => env('INVENTORY_GL_ASSET_ACCOUNT', '1200'),
        'grir_clearing_account' => env('INVENTORY_GL_GRIR_ACCOUNT', '2000'),
        'cogs_account' => env('INVENTORY_GL_COGS_ACCOUNT', '5000'),
        'adjustment_account' => env('INVENTORY_GL_ADJUSTMENT_ACCOUNT', '5100'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Valuation Method
    |--------------------------------------------------------------------------
    |
    | Supported values: fifo, weighted_average, standard_cost
    |
    */
    'default_valuation_method' => env('INVENTORY_VALUATION_METHOD', 'weighted_average'),

    /*
    |--------------------------------------------------------------------------
    | Stock Control
    |--------------------------------------------------------------------------
    */
    'allow_negative_stock' => env('INVENTORY_ALLOW_NEGATIVE_STOCK', false),

    /*
    |--------------------------------------------------------------------------
    | Reservation Settings
    |--------------------------------------------------------------------------
    */
    'default_reservation_ttl_hours' => env('INVENTORY_RESERVATION_TTL_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | FEFO (First-Expiry-First-Out) Settings
    |--------------------------------------------------------------------------
    */
    'fefo_enabled' => env('INVENTORY_FEFO_ENABLED', true),
    'expiry_warning_days' => env('INVENTORY_EXPIRY_WARNING_DAYS', 30),
];
