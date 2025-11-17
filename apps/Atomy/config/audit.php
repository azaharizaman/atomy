<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Retention Period
    |--------------------------------------------------------------------------
    |
    | Number of days to retain audit logs before automatic purging.
    | Satisfies: BUS-AUD-0147
    |
    */
    'default_retention_days' => env('AUDIT_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Asynchronous Logging
    |--------------------------------------------------------------------------
    |
    | Enable asynchronous logging via queue to prevent performance impact.
    | Satisfies: FUN-AUD-0196
    |
    */
    'async_logging' => env('AUDIT_ASYNC_LOGGING', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    |
    | The queue name to use for asynchronous audit logging.
    |
    */
    'queue_name' => env('AUDIT_QUEUE_NAME', 'audit-logs'),

    /*
    |--------------------------------------------------------------------------
    | Sensitive Field Patterns
    |--------------------------------------------------------------------------
    |
    | Field names or regex patterns to automatically mask in audit logs.
    | Satisfies: FUN-AUD-0192
    |
    */
    'sensitive_fields' => [
        'password',
        'password_confirmation',
        'token',
        'secret',
        'api_key',
        'private_key',
        'access_token',
        'refresh_token',
        'credit_card',
        'cvv',
        'ssn',
        'social_security_number',
        '/.*_token$/',      // Regex: any field ending with _token
        '/.*_secret$/',     // Regex: any field ending with _secret
        '/.*_key$/',        // Regex: any field ending with _key
    ],

    /*
    |--------------------------------------------------------------------------
    | High-Value Entity Types
    |--------------------------------------------------------------------------
    |
    | Entity types that should default to Critical audit level (4).
    | Satisfies: BUS-AUD-0149
    |
    */
    'high_value_entities' => [
        'User',
        'Role',
        'Permission',
        'JournalEntry',
        'Payment',
        'Invoice',
        'PurchaseOrder',
        'GoodsReceipt',
        'PayrollRun',
        'Payslip',
        'BankTransaction',
        'TaxReturn',
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Notifications
    |--------------------------------------------------------------------------
    |
    | Enable notifications for high-value (critical) audit activities.
    | Satisfies: FUN-AUD-0197
    |
    */
    'notifications_enabled' => env('AUDIT_NOTIFICATIONS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Notification Recipients
    |--------------------------------------------------------------------------
    |
    | Email addresses or user IDs to notify for critical audit activities.
    |
    */
    'notification_recipients' => [
        // 'security@company.com',
        // 'admin@company.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Size for Purging
    |--------------------------------------------------------------------------
    |
    | Number of records to delete in each batch when purging expired logs.
    | Satisfies: BUS-AUD-0151
    |
    */
    'purge_batch_size' => env('AUDIT_PURGE_BATCH_SIZE', 1000),

    /*
    |--------------------------------------------------------------------------
    | Enable Full-Text Search
    |--------------------------------------------------------------------------
    |
    | Enable full-text search index on audit logs (MySQL/MariaDB specific).
    | Satisfies: FUN-AUD-0189
    |
    */
    'fulltext_search' => env('AUDIT_FULLTEXT_SEARCH', true),

];
