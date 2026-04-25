<?php

declare(strict_types=1);

use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

return [
    'api_version' => 'v1',
    'api_prefix' => 'api/v1',

    'pagination' => [
        'default_per_page' => (int) env('ATOMY_DEFAULT_PER_PAGE', 25),
        'max_per_page' => (int) env('ATOMY_MAX_PER_PAGE', 100),
    ],

    'upload' => [
        'max_file_size_mb' => (int) env('ATOMY_MAX_UPLOAD_MB', 50),
        'allowed_mime_types' => [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'text/csv',
        ],
    ],

    'mfa' => [
        'device_trust_days' => (int) env('ATOMY_MFA_DEVICE_TRUST_DAYS', 30),
    ],

    'approval' => [
        'sla_hours' => (int) env('ATOMY_APPROVAL_SLA_HOURS', 48),
        'max_snooze_hours' => (int) env('ATOMY_APPROVAL_MAX_SNOOZE_HOURS', 72),
    ],

    'comparison' => [
        'min_vendors_final' => (int) env('ATOMY_MIN_VENDORS_FINAL', 2),
        'min_vendors_preview' => (int) env('ATOMY_MIN_VENDORS_PREVIEW', 1),
        'confidence_threshold' => (float) env('ATOMY_CONFIDENCE_THRESHOLD', 0.7),
    ],

    'quote_intelligence' => [
        'mode' => (string) env('QUOTE_INTELLIGENCE_MODE', 'deterministic'),
        'llm' => [
            'provider' => (string) env('QUOTE_INTELLIGENCE_LLM_PROVIDER', ''),
            'model' => (string) env('QUOTE_INTELLIGENCE_LLM_MODEL', ''),
            'base_url' => (string) env('QUOTE_INTELLIGENCE_LLM_BASE_URL', ''),
            'api_key' => (string) env('QUOTE_INTELLIGENCE_LLM_API_KEY', ''),
            'timeout_seconds' => (int) env('QUOTE_INTELLIGENCE_LLM_TIMEOUT_SECONDS', 30),
        ],
    ],

    'ai' => [
        'mode' => (string) env('AI_MODE', AiStatusSchema::MODE_DETERMINISTIC),
        'provider' => [
            'key' => (string) env('AI_PROVIDER', 'openrouter'),
            'name' => (string) env('AI_PROVIDER_NAME', ''),
            'default_auth_token' => (string) env(
                'AI_DEFAULT_AUTH_TOKEN',
                (string) env('AI_AUTH_TOKEN', ''),
            ),
            'default_timeout_seconds' => (int) env(
                'AI_DEFAULT_TIMEOUT_SECONDS',
                (int) env('AI_TIMEOUT_SECONDS', 10),
            ),
            // Total attempts, not retries: 1 = no retries, 2 = 1 retry, capped by ProviderAiTransport::MAX_RETRY_ATTEMPTS.
            'default_retry_attempts' => (int) env('AI_DEFAULT_RETRY_ATTEMPTS', 1),
            'default_retry_backoff_ms' => (int) env('AI_DEFAULT_RETRY_BACKOFF_MS', 0),
        ],
        'operations' => [
            'log_channel' => (string) env('AI_LOG_CHANNEL', 'ai_operations'),
            'alert_cooldown_seconds' => (int) env('AI_ALERT_COOLDOWN_SECONDS', 300),
            'alert_recipient_emails' => array_values(array_filter(array_map(
                static fn (string $email): string => trim($email),
                explode(',', (string) env('AI_ALERT_RECIPIENT_EMAILS', '')),
            ))),
        ],
        'endpoints' => [
            'document' => [
                'uri' => (string) env('AI_DOCUMENT_ENDPOINT', (string) env('HF_DOCUMENT_ENDPOINT', '')),
                'auth_token' => (string) env('AI_DOCUMENT_AUTH_TOKEN', (string) env('HF_DOCUMENT_AUTH_TOKEN', '')),
                'timeout_seconds' => (int) env(
                    'AI_DOCUMENT_TIMEOUT_SECONDS',
                    (int) env('HF_DOCUMENT_TIMEOUT_SECONDS', 10),
                ),
                'retry_attempts' => (int) env('AI_DOCUMENT_RETRY_ATTEMPTS', (int) env('HF_DOCUMENT_RETRY_ATTEMPTS', 0)),
                'retry_backoff_ms' => (int) env('AI_DOCUMENT_RETRY_BACKOFF_MS', (int) env('HF_DOCUMENT_RETRY_BACKOFF_MS', 0)),
                'model_id' => (string) env('AI_DOCUMENT_MODEL_ID', (string) env('HF_DOCUMENT_MODEL_ID', '')),
                'model_revision' => (string) env('AI_DOCUMENT_MODEL_REVISION', (string) env('HF_DOCUMENT_MODEL_REVISION', '')),
                'health_url' => (string) env('AI_DOCUMENT_HEALTH_URL', (string) env('HF_DOCUMENT_HEALTH_URL', '')),
                'health_path' => (string) env('AI_DOCUMENT_HEALTH_PATH', (string) env('HF_DOCUMENT_HEALTH_PATH', '/health')),
                'health_method' => (string) env('AI_DOCUMENT_HEALTH_METHOD', (string) env('HF_DOCUMENT_HEALTH_METHOD', 'GET')),
            ],
            'normalization' => [
                'uri' => (string) env('AI_NORMALIZATION_ENDPOINT', (string) env('HF_NORMALIZATION_ENDPOINT', '')),
                'auth_token' => (string) env('AI_NORMALIZATION_AUTH_TOKEN', (string) env('HF_NORMALIZATION_AUTH_TOKEN', '')),
                'timeout_seconds' => (int) env(
                    'AI_NORMALIZATION_TIMEOUT_SECONDS',
                    (int) env('HF_NORMALIZATION_TIMEOUT_SECONDS', 10),
                ),
                'retry_attempts' => (int) env('AI_NORMALIZATION_RETRY_ATTEMPTS', (int) env('HF_NORMALIZATION_RETRY_ATTEMPTS', 0)),
                'retry_backoff_ms' => (int) env('AI_NORMALIZATION_RETRY_BACKOFF_MS', (int) env('HF_NORMALIZATION_RETRY_BACKOFF_MS', 0)),
                'model_id' => (string) env('AI_NORMALIZATION_MODEL_ID', (string) env('HF_NORMALIZATION_MODEL_ID', '')),
                'model_revision' => (string) env('AI_NORMALIZATION_MODEL_REVISION', (string) env('HF_NORMALIZATION_MODEL_REVISION', '')),
                'health_url' => (string) env('AI_NORMALIZATION_HEALTH_URL', (string) env('HF_NORMALIZATION_HEALTH_URL', '')),
                'health_path' => (string) env('AI_NORMALIZATION_HEALTH_PATH', (string) env('HF_NORMALIZATION_HEALTH_PATH', '/health')),
                'health_method' => (string) env('AI_NORMALIZATION_HEALTH_METHOD', (string) env('HF_NORMALIZATION_HEALTH_METHOD', 'GET')),
            ],
            'sourcing_recommendation' => [
                'uri' => (string) env(
                    'AI_SOURCING_RECOMMENDATION_ENDPOINT',
                    (string) env('HF_SOURCING_RECOMMENDATION_ENDPOINT', ''),
                ),
                'auth_token' => (string) env(
                    'AI_SOURCING_RECOMMENDATION_AUTH_TOKEN',
                    (string) env('HF_SOURCING_RECOMMENDATION_AUTH_TOKEN', ''),
                ),
                'timeout_seconds' => (int) env(
                    'AI_SOURCING_RECOMMENDATION_TIMEOUT_SECONDS',
                    (int) env('HF_SOURCING_RECOMMENDATION_TIMEOUT_SECONDS', 10),
                ),
                'retry_attempts' => (int) env(
                    'AI_SOURCING_RECOMMENDATION_RETRY_ATTEMPTS',
                    (int) env('HF_SOURCING_RECOMMENDATION_RETRY_ATTEMPTS', 0),
                ),
                'retry_backoff_ms' => (int) env(
                    'AI_SOURCING_RECOMMENDATION_RETRY_BACKOFF_MS',
                    (int) env('HF_SOURCING_RECOMMENDATION_RETRY_BACKOFF_MS', 0),
                ),
                'model_id' => (string) env(
                    'AI_SOURCING_RECOMMENDATION_MODEL_ID',
                    (string) env('HF_SOURCING_RECOMMENDATION_MODEL_ID', ''),
                ),
                'model_revision' => (string) env(
                    'AI_SOURCING_RECOMMENDATION_MODEL_REVISION',
                    (string) env('HF_SOURCING_RECOMMENDATION_MODEL_REVISION', ''),
                ),
                'health_url' => (string) env(
                    'AI_SOURCING_RECOMMENDATION_HEALTH_URL',
                    (string) env('HF_SOURCING_RECOMMENDATION_HEALTH_URL', ''),
                ),
                'health_path' => (string) env(
                    'AI_SOURCING_RECOMMENDATION_HEALTH_PATH',
                    (string) env('HF_SOURCING_RECOMMENDATION_HEALTH_PATH', '/health'),
                ),
                'health_method' => (string) env(
                    'AI_SOURCING_RECOMMENDATION_HEALTH_METHOD',
                    (string) env('HF_SOURCING_RECOMMENDATION_HEALTH_METHOD', 'GET'),
                ),
            ],
            'comparison_award' => [
                'uri' => (string) env('AI_COMPARISON_AWARD_ENDPOINT', (string) env('HF_COMPARISON_AWARD_ENDPOINT', '')),
                'auth_token' => (string) env(
                    'AI_COMPARISON_AWARD_AUTH_TOKEN',
                    (string) env('HF_COMPARISON_AWARD_AUTH_TOKEN', ''),
                ),
                'timeout_seconds' => (int) env(
                    'AI_COMPARISON_AWARD_TIMEOUT_SECONDS',
                    (int) env('HF_COMPARISON_AWARD_TIMEOUT_SECONDS', 10),
                ),
                'retry_attempts' => (int) env('AI_COMPARISON_AWARD_RETRY_ATTEMPTS', (int) env('HF_COMPARISON_AWARD_RETRY_ATTEMPTS', 0)),
                'retry_backoff_ms' => (int) env('AI_COMPARISON_AWARD_RETRY_BACKOFF_MS', (int) env('HF_COMPARISON_AWARD_RETRY_BACKOFF_MS', 0)),
                'model_id' => (string) env('AI_COMPARISON_AWARD_MODEL_ID', (string) env('HF_COMPARISON_AWARD_MODEL_ID', '')),
                'model_revision' => (string) env('AI_COMPARISON_AWARD_MODEL_REVISION', (string) env('HF_COMPARISON_AWARD_MODEL_REVISION', '')),
                'health_url' => (string) env('AI_COMPARISON_AWARD_HEALTH_URL', (string) env('HF_COMPARISON_AWARD_HEALTH_URL', '')),
                'health_path' => (string) env('AI_COMPARISON_AWARD_HEALTH_PATH', (string) env('HF_COMPARISON_AWARD_HEALTH_PATH', '/health')),
                'health_method' => (string) env('AI_COMPARISON_AWARD_HEALTH_METHOD', (string) env('HF_COMPARISON_AWARD_HEALTH_METHOD', 'GET')),
            ],
            'insight' => [
                'uri' => (string) env('AI_INSIGHT_ENDPOINT', (string) env('HF_INSIGHT_ENDPOINT', '')),
                'auth_token' => (string) env('AI_INSIGHT_AUTH_TOKEN', (string) env('HF_INSIGHT_AUTH_TOKEN', '')),
                'timeout_seconds' => (int) env('AI_INSIGHT_TIMEOUT_SECONDS', (int) env('HF_INSIGHT_TIMEOUT_SECONDS', 10)),
                'retry_attempts' => (int) env('AI_INSIGHT_RETRY_ATTEMPTS', (int) env('HF_INSIGHT_RETRY_ATTEMPTS', 0)),
                'retry_backoff_ms' => (int) env('AI_INSIGHT_RETRY_BACKOFF_MS', (int) env('HF_INSIGHT_RETRY_BACKOFF_MS', 0)),
                'model_id' => (string) env('AI_INSIGHT_MODEL_ID', (string) env('HF_INSIGHT_MODEL_ID', '')),
                'model_revision' => (string) env('AI_INSIGHT_MODEL_REVISION', (string) env('HF_INSIGHT_MODEL_REVISION', '')),
                'health_url' => (string) env('AI_INSIGHT_HEALTH_URL', (string) env('HF_INSIGHT_HEALTH_URL', '')),
                'health_path' => (string) env('AI_INSIGHT_HEALTH_PATH', (string) env('HF_INSIGHT_HEALTH_PATH', '/health')),
                'health_method' => (string) env('AI_INSIGHT_HEALTH_METHOD', (string) env('HF_INSIGHT_HEALTH_METHOD', 'GET')),
            ],
            'governance' => [
                'uri' => (string) env('AI_GOVERNANCE_ENDPOINT', (string) env('HF_GOVERNANCE_ENDPOINT', '')),
                'auth_token' => (string) env('AI_GOVERNANCE_AUTH_TOKEN', (string) env('HF_GOVERNANCE_AUTH_TOKEN', '')),
                'timeout_seconds' => (int) env('AI_GOVERNANCE_TIMEOUT_SECONDS', (int) env('HF_GOVERNANCE_TIMEOUT_SECONDS', 10)),
                'retry_attempts' => (int) env('AI_GOVERNANCE_RETRY_ATTEMPTS', (int) env('HF_GOVERNANCE_RETRY_ATTEMPTS', 0)),
                'retry_backoff_ms' => (int) env('AI_GOVERNANCE_RETRY_BACKOFF_MS', (int) env('HF_GOVERNANCE_RETRY_BACKOFF_MS', 0)),
                'model_id' => (string) env('AI_GOVERNANCE_MODEL_ID', (string) env('HF_GOVERNANCE_MODEL_ID', '')),
                'model_revision' => (string) env('AI_GOVERNANCE_MODEL_REVISION', (string) env('HF_GOVERNANCE_MODEL_REVISION', '')),
                'health_url' => (string) env('AI_GOVERNANCE_HEALTH_URL', (string) env('HF_GOVERNANCE_HEALTH_URL', '')),
                'health_path' => (string) env('AI_GOVERNANCE_HEALTH_PATH', (string) env('HF_GOVERNANCE_HEALTH_PATH', '/health')),
                'health_method' => (string) env('AI_GOVERNANCE_HEALTH_METHOD', (string) env('HF_GOVERNANCE_HEALTH_METHOD', 'GET')),
            ],
        ],
    ],
];
