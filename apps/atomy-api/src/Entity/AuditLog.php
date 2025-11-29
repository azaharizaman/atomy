<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nexus\AuditLogger\Contracts\AuditLogInterface;

/**
 * Audit log entity implementing Nexus AuditLogger contract.
 * 
 * Stores security-relevant events for compliance and debugging.
 */
#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_logs')]
#[ORM\Index(columns: ['log_name'], name: 'idx_audit_log_name')]
#[ORM\Index(columns: ['subject_type', 'subject_id'], name: 'idx_audit_subject')]
#[ORM\Index(columns: ['causer_type', 'causer_id'], name: 'idx_audit_causer')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_audit_tenant')]
#[ORM\Index(columns: ['created_at'], name: 'idx_audit_created')]
#[ORM\Index(columns: ['expires_at'], name: 'idx_audit_expires')]
#[ORM\Index(columns: ['batch_uuid'], name: 'idx_audit_batch')]
#[ORM\Index(columns: ['level'], name: 'idx_audit_level')]
class AuditLog implements AuditLogInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    private string $id;

    #[ORM\Column(name: 'log_name', type: Types::STRING, length: 100)]
    private string $logName;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[ORM\Column(name: 'subject_type', type: Types::STRING, length: 100, nullable: true)]
    private ?string $subjectType = null;

    #[ORM\Column(name: 'subject_id', type: Types::STRING, length: 100, nullable: true)]
    private ?string $subjectId = null;

    #[ORM\Column(name: 'causer_type', type: Types::STRING, length: 100, nullable: true)]
    private ?string $causerType = null;

    #[ORM\Column(name: 'causer_id', type: Types::STRING, length: 100, nullable: true)]
    private ?string $causerId = null;

    #[ORM\Column(type: Types::JSON)]
    private array $properties = [];

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $event = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $level = 2; // Default: Medium

    #[ORM\Column(name: 'batch_uuid', type: Types::STRING, length: 36, nullable: true)]
    private ?string $batchUuid = null;

    #[ORM\Column(name: 'ip_address', type: Types::STRING, length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(name: 'user_agent', type: Types::STRING, length: 500, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(name: 'tenant_id', type: Types::STRING, length: 100, nullable: true)]
    private ?string $tenantId = null;

    #[ORM\Column(name: 'retention_days', type: Types::INTEGER)]
    private int $retentionDays = 90; // Default 90 days per BUS-AUD-0147

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $this->createdAt->modify("+{$this->retentionDays} days");
    }

    // ==================== Getters (from AuditLogInterface) ====================

    public function getId(): string
    {
        return $this->id;
    }

    public function getLogName(): string
    {
        return $this->logName;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getSubjectType(): ?string
    {
        return $this->subjectType;
    }

    public function getSubjectId(): ?string
    {
        return $this->subjectId;
    }

    public function getCauserType(): ?string
    {
        return $this->causerType;
    }

    public function getCauserId(): ?string
    {
        return $this->causerId;
    }

    public function getProperties(): array
    {
        return $this->properties;
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
        return $this->batchUuid;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function getRetentionDays(): int
    {
        return $this->retentionDays;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return new \DateTimeImmutable() > $this->expiresAt;
    }

    // ==================== Setters ====================

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setLogName(string $logName): self
    {
        $this->logName = $logName;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setSubjectType(?string $subjectType): self
    {
        $this->subjectType = $subjectType;
        return $this;
    }

    public function setSubjectId(int|string|null $subjectId): self
    {
        $this->subjectId = $subjectId !== null ? (string) $subjectId : null;
        return $this;
    }

    public function setCauserType(?string $causerType): self
    {
        $this->causerType = $causerType;
        return $this;
    }

    public function setCauserId(int|string|null $causerId): self
    {
        $this->causerId = $causerId !== null ? (string) $causerId : null;
        return $this;
    }

    public function setProperties(array $properties): self
    {
        $this->properties = $properties;
        return $this;
    }

    public function setEvent(?string $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;
        return $this;
    }

    public function setBatchUuid(?string $batchUuid): self
    {
        $this->batchUuid = $batchUuid;
        return $this;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent !== null 
            ? substr($userAgent, 0, 500) 
            : null;
        return $this;
    }

    public function setTenantId(int|string|null $tenantId): self
    {
        $this->tenantId = $tenantId !== null ? (string) $tenantId : null;
        return $this;
    }

    public function setRetentionDays(int $retentionDays): self
    {
        $this->retentionDays = $retentionDays;
        // Recalculate expires_at based on new retention
        $this->expiresAt = $this->createdAt->modify("+{$retentionDays} days");
        return $this;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        // Recalculate expires_at based on new created date
        $this->expiresAt = $createdAt->modify("+{$this->retentionDays} days");
        return $this;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    // ==================== Helper Methods ====================

    /**
     * Convert to array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'log_name' => $this->logName,
            'description' => $this->description,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'causer_type' => $this->causerType,
            'causer_id' => $this->causerId,
            'properties' => $this->properties,
            'event' => $this->event,
            'level' => $this->level,
            'batch_uuid' => $this->batchUuid,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'tenant_id' => $this->tenantId,
            'retention_days' => $this->retentionDays,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'expires_at' => $this->expiresAt->format(\DateTimeInterface::ATOM),
            'is_expired' => $this->isExpired(),
        ];
    }
}
