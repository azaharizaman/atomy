<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Nexus\AuditLogger\Contracts\AuditLogInterface;

final class AuditLog extends Model implements AuditLogInterface
{
    use HasUlids;

    protected $table = 'audit_logs';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
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
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'level' => 'integer',
        'retention_days' => 'integer',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getId(): string
    {
        return (string) $this->getKey();
    }

    public function getLogName(): string
    {
        return (string) $this->log_name;
    }

    public function getDescription(): string
    {
        return (string) $this->description;
    }

    public function getSubjectType(): ?string
    {
        $v = $this->subject_type;

        return is_string($v) && trim($v) !== '' ? $v : null;
    }

    public function getSubjectId()
    {
        $v = $this->subject_id;

        return is_string($v) && trim($v) !== '' ? $v : null;
    }

    public function getCauserType(): ?string
    {
        $v = $this->causer_type;

        return is_string($v) && trim($v) !== '' ? $v : null;
    }

    public function getCauserId()
    {
        $v = $this->causer_id;

        return is_string($v) && trim($v) !== '' ? $v : null;
    }

    public function getProperties(): array
    {
        $p = $this->properties;

        return is_array($p) ? $p : [];
    }

    public function getEvent(): ?string
    {
        $v = $this->event;

        return is_string($v) && trim($v) !== '' ? $v : null;
    }

    public function getLevel(): int
    {
        return (int) ($this->level ?? 1);
    }

    public function getBatchUuid(): ?string
    {
        $v = $this->batch_uuid;

        return is_string($v) && trim($v) !== '' ? $v : null;
    }

    public function getIpAddress(): ?string
    {
        $v = $this->ip_address;

        return is_string($v) && trim($v) !== '' ? $v : null;
    }

    public function getUserAgent(): ?string
    {
        $v = $this->user_agent;

        return is_string($v) && trim($v) !== '' ? $v : null;
    }

    public function getTenantId()
    {
        $v = $this->tenant_id;

        return is_string($v) && trim($v) !== '' ? $v : null;
    }

    public function getRetentionDays(): int
    {
        $days = (int) ($this->retention_days ?? 90);

        return $days < 1 ? 1 : $days;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        $createdAt = $this->created_at;
        if ($createdAt instanceof \DateTimeInterface) {
            if ($createdAt instanceof \DateTimeImmutable) {
                return $createdAt;
            }

            return \DateTimeImmutable::createFromInterface($createdAt);
        }

        return new \DateTimeImmutable();
    }

    public function getExpiresAt(): \DateTimeInterface
    {
        $expiresAt = $this->expires_at;
        if ($expiresAt instanceof \DateTimeInterface) {
            if ($expiresAt instanceof \DateTimeImmutable) {
                return $expiresAt;
            }

            return \DateTimeImmutable::createFromInterface($expiresAt);
        }

        return $this->getCreatedAt()->modify(sprintf('+%d days', $this->getRetentionDays()));
    }

    public function isExpired(): bool
    {
        return $this->getExpiresAt() <= new \DateTimeImmutable();
    }
}
