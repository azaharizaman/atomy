<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Notification Channels
    |--------------------------------------------------------------------------
    |
    | Define the default channels to use when no specific channels are requested.
    | Available: email, sms, push, in_app
    |
    */
    'default_channels' => ['email', 'in_app'],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting per channel to prevent spam.
    | Format: [limit => count, window => seconds]
    |
    */
    'rate_limits' => [
        'email' => ['limit' => 10, 'window' => 60],
        'sms' => ['limit' => 5, 'window' => 60],
        'push' => ['limit' => 20, 'window' => 60],
        'in_app' => ['limit' => 50, 'window' => 60],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification History Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to retain notification history before automatic purge.
    | Set to 0 to disable automatic purging.
    |
    */
    'history_retention_days' => 90,

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure notification queue behavior.
    |
    */
    'queue' => [
        'enabled' => true,
        'connection' => env('NOTIFIER_QUEUE_CONNECTION', 'database'),
        'queue_name' => env('NOTIFIER_QUEUE_NAME', 'notifications'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure retry behavior for failed notifications.
    |
    */
    'retry' => [
        'max_attempts' => 3,
        'backoff_minutes' => [5, 15, 60], // Retry after 5min, 15min, 60min
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Configuration
    |--------------------------------------------------------------------------
    |
    | Configure notification template behavior.
    |
    */
    'templates' => [
        'enabled' => true,
        'cache_ttl' => 3600, // Cache templates for 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configure external notification providers via Connector package.
    |
    */
    'providers' => [
        'email' => [
            'connector' => 'sendgrid', // or 'smtp', 'mailgun', 'ses'
        ],
        'sms' => [
            'connector' => 'twilio', // or 'messagebird', 'nexmo'
        ],
        'push' => [
            'connector' => 'fcm', // Firebase Cloud Messaging
        ],
    ],
];
