<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Email Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which email service adapter to use. Available options:
    | - 'mailchimp': Mailchimp Transactional (Mandrill)
    | - 'sendgrid': SendGrid Email API
    |
    */
    'email_vendor' => env('CONNECTOR_EMAIL_VENDOR', 'mailchimp'),

    'email' => [
        'mailchimp' => [
            'api_key' => env('MAILCHIMP_API_KEY'),
            'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
            'from_name' => env('MAIL_FROM_NAME', 'Nexus'),
        ],

        'sendgrid' => [
            'api_key' => env('SENDGRID_API_KEY'),
            'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
            'from_name' => env('MAIL_FROM_NAME', 'Nexus'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which SMS service adapter to use. Available options:
    | - 'twilio': Twilio SMS
    |
    */
    'sms_vendor' => env('CONNECTOR_SMS_VENDOR', 'twilio'),

    'sms' => [
        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from_number' => env('TWILIO_FROM_NUMBER'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Configuration
    |--------------------------------------------------------------------------
    |
    | Default circuit breaker settings for all services.
    | Individual services can override these values.
    |
    */
    'circuit_breaker' => [
        'failure_threshold' => env('CONNECTOR_CIRCUIT_FAILURE_THRESHOLD', 5),
        'timeout_seconds' => env('CONNECTOR_CIRCUIT_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Policy Configuration
    |--------------------------------------------------------------------------
    |
    | Default retry policy for all external requests.
    |
    */
    'retry_policy' => [
        'max_attempts' => env('CONNECTOR_RETRY_MAX_ATTEMPTS', 3),
        'initial_delay_ms' => env('CONNECTOR_RETRY_INITIAL_DELAY', 1000),
        'multiplier' => env('CONNECTOR_RETRY_MULTIPLIER', 2.0),
        'max_delay_ms' => env('CONNECTOR_RETRY_MAX_DELAY', 30000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Log Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to retain integration logs before automatic purging.
    |
    */
    'log_retention_days' => env('CONNECTOR_LOG_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Monitored Services
    |--------------------------------------------------------------------------
    |
    | List of services to monitor in the status endpoint.
    | This should match the service names used in your integrations.
    |
    */
    'monitored_services' => [
        'mailchimp',
        'sendgrid',
        'twilio',
    ],

    /*
    |--------------------------------------------------------------------------
    | Service-Specific Endpoints
    |--------------------------------------------------------------------------
    |
    | Define custom endpoints and configurations for specific services.
    |
    */
    'services' => [
        // Example service configuration
        // 'payment_gateway' => [
        //     'vendor' => 'stripe',
        //     'endpoint' => 'https://api.stripe.com/v1',
        //     'timeout' => 30,
        // ],
    ],
];
