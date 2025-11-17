<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\AuditLogger\Contracts\AuditLogInterface;

/**
 * Eloquent model for audit_logs table
 * Implements AuditLogInterface from package
 * Satisfies: ARC-AUD-0006 (Eloquent models in application layer)
 *
 * @property int $id
 * @property string $log_name
 * @property string $description
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $causer_type
 * @property int|null $causer_id
 * @property array $properties
 * @property string|null $event
 * @property int $level
 * @property string|null $batch_uuid
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int|null $tenant_id
 * @property int $retention_days
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $expires_at
 */
class AuditLog extends Model implements AuditLogInterface
{
    public const UPDATED_AT = null; // Audit logs are immutable, no updated_at

    protected $table = 'audit_logs';

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'event',
        'level',
        'batch_uuid',
        'ip_address',
        'user_agent',
        'tenant_id',
        'retention_days',
        'expires_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'level' => 'integer',
        'retention_days' => 'integer',
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Implement AuditLogInterface methods

    public function getId(): int
    {
        return $this->id;
    }

    public function getLogName(): string
    {
        return $this->log_name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getSubjectType(): ?string
    {
        return $this->subject_type;
    }

    public function getSubjectId(): int|string|null
    {
        return $this->subject_id;
    }

    public function getCauserType(): ?string
    {
        return $this->causer_type;
    }

    public function getCauserId(): int|string|null
    {
        return $this->causer_id;
    }

    public function getProperties(): array
    {
        return $this->properties ?? [];
    }

    public function getEvent(): ?string
    {
        return $this->event;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getBatchUuid(): ?string
    {
        return $this->batch_uuid;
    }

    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    public function getTenantId(): int|string|null
    {
        return $this->tenant_id;
    }

    public function getRetentionDays(): int
    {
        return $this->retention_days;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expires_at;
    }

    public function isExpired(): bool
    {
        return now()->greaterThanOrEqualTo($this->expires_at);
    }

    // Eloquent relationships

    /**
     * Get the subject model (polymorphic)
     */
    public function subject()
    {
        return $this->morphTo('subject');
    }

    /**
     * Get the causer model (polymorphic)
     */
    public function causer()
    {
        return $this->morphTo('causer');
    }

    // Query scopes

    public function scopeForSubject($query, string $type, int|string $id)
    {
        return $query->where('subject_type', $type)->where('subject_id', $id);
    }

    public function scopeForCauser($query, string $type, int|string $id)
    {
        return $query->where('causer_type', $type)->where('causer_id', $id);
    }

    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    public function scopeByBatch($query, string $uuid)
    {
        return $query->where('batch_uuid', $uuid);
    }

    public function scopeExpired($query, ?\DateTimeInterface $beforeDate = null)
    {
        $beforeDate = $beforeDate ?? now();
        return $query->where('expires_at', '<=', $beforeDate);
    }

    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeForTenant($query, int|string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
