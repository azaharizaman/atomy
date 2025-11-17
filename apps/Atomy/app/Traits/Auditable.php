<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Str;

/**
 * Trait for automatically auditing model changes
 * Satisfies: ARC-AUD-0008 (Traits in application layer)
 * Implements: FUN-AUD-0185 (automatic CRUD capture)
 *
 * Usage: Add this trait to any Eloquent model that needs audit logging
 *
 * @package App\Traits
 */
trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->auditCreated();
        });

        static::updated(function ($model) {
            $model->auditUpdated();
        });

        static::deleted(function ($model) {
            $model->auditDeleted();
        });
    }

    /**
     * Log model creation
     */
    protected function auditCreated(): void
    {
        $this->createAuditLog('created', 'created', [
            'attributes' => $this->getAuditableAttributes(),
        ]);
    }

    /**
     * Log model updates with before/after state
     * Satisfies: FUN-AUD-0186
     */
    protected function auditUpdated(): void
    {
        $this->createAuditLog('updated', 'updated', [
            'old' => $this->getOriginal(),
            'new' => $this->getAttributes(),
            'changes' => $this->getChanges(),
        ]);
    }

    /**
     * Log model deletion
     */
    protected function auditDeleted(): void
    {
        $this->createAuditLog('deleted', 'deleted', [
            'attributes' => $this->getAuditableAttributes(),
        ]);
    }

    /**
     * Create audit log entry
     */
    protected function createAuditLog(string $logName, string $event, array $properties): void
    {
        $subjectType = class_basename($this);
        $causerType = auth()->check() ? class_basename(auth()->user()) : null;
        $causerId = auth()->id();

        AuditLog::create([
            'log_name' => Str::snake($subjectType) . '_' . $logName,
            'description' => "{$subjectType} {$event}",
            'subject_type' => get_class($this),
            'subject_id' => $this->getKey(),
            'causer_type' => $causerType ? get_class(auth()->user()) : null,
            'causer_id' => $causerId,
            'properties' => $this->maskSensitiveData($properties),
            'event' => $event,
            'level' => $this->getAuditLevel(),
            'batch_uuid' => request()->header('X-Batch-UUID'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'tenant_id' => $this->getTenantIdForAudit(),
            'retention_days' => $this->getAuditRetentionDays(),
            'expires_at' => now()->addDays($this->getAuditRetentionDays()),
        ]);
    }

    /**
     * Get attributes to include in audit log
     */
    protected function getAuditableAttributes(): array
    {
        $attributes = $this->getAttributes();

        // Remove sensitive fields if defined
        if (method_exists($this, 'getAuditExclude')) {
            $exclude = $this->getAuditExclude();
            $attributes = array_diff_key($attributes, array_flip($exclude));
        }

        return $attributes;
    }

    /**
     * Get audit level for this model
     * Satisfies: BUS-AUD-0149
     */
    protected function getAuditLevel(): int
    {
        if (method_exists($this, 'getAuditLevelOverride')) {
            return $this->getAuditLevelOverride();
        }

        // Default to High for high-value entities
        $highValueEntities = config('audit.high_value_entities', [
            'User', 'Role', 'Permission', 'JournalEntry', 'Payment'
        ]);

        return in_array(class_basename($this), $highValueEntities) ? 4 : 2;
    }

    /**
     * Get retention days for audit logs
     */
    protected function getAuditRetentionDays(): int
    {
        if (method_exists($this, 'getAuditRetentionOverride')) {
            return $this->getAuditRetentionOverride();
        }

        return config('audit.default_retention_days', 90);
    }

    /**
     * Get tenant ID for multi-tenancy
     */
    protected function getTenantIdForAudit(): ?int
    {
        if (property_exists($this, 'tenant_id')) {
            return $this->tenant_id;
        }

        return null;
    }

    /**
     * Mask sensitive data
     * Satisfies: FUN-AUD-0192
     */
    protected function maskSensitiveData(array $data): array
    {
        $sensitiveFields = config('audit.sensitive_fields', [
            'password', 'password_confirmation', 'token', 'secret', 
            'api_key', 'private_key', 'access_token', 'refresh_token'
        ]);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            } elseif ($this->isSensitiveField($key, $sensitiveFields)) {
                $data[$key] = '***MASKED***';
            }
        }

        return $data;
    }

    /**
     * Check if field is sensitive
     */
    protected function isSensitiveField(string $field, array $patterns): bool
    {
        $lowerField = strtolower($field);

        foreach ($patterns as $pattern) {
            if (str_contains($lowerField, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }
}
