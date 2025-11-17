<?php

declare(strict_types=1);

namespace App\Services;

use Nexus\AuditLogger\Contracts\AuditConfigInterface;

/**
 * Configuration implementation for audit logging
 * Implements AuditConfigInterface from package
 *
 * @package App\Services
 */
class AuditConfig implements AuditConfigInterface
{
    public function getDefaultRetentionDays(): int
    {
        return config('audit.default_retention_days', 90);
    }

    public function getDefaultLevelForEntity(string $entityType): int
    {
        $highValueEntities = $this->getHighValueEntityTypes();
        
        return in_array($entityType, $highValueEntities) ? 4 : 2;
    }

    public function isAsyncLoggingEnabled(): bool
    {
        return config('audit.async_logging', true);
    }

    public function getQueueName(): string
    {
        return config('audit.queue_name', 'audit-logs');
    }

    public function getSensitiveFieldPatterns(): array
    {
        return config('audit.sensitive_fields', [
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
            '/.*_token$/',
            '/.*_secret$/',
        ]);
    }

    public function getHighValueEntityTypes(): array
    {
        return config('audit.high_value_entities', [
            'User',
            'Role',
            'Permission',
            'JournalEntry',
            'Payment',
            'Invoice',
            'PurchaseOrder',
            'PayrollRun',
        ]);
    }

    public function areNotificationsEnabled(): bool
    {
        return config('audit.notifications_enabled', false);
    }

    public function getNotificationRecipients(): array
    {
        return config('audit.notification_recipients', []);
    }
}
