<?php

declare(strict_types=1);

namespace Nexus\AuditLogger\Services;

use Nexus\AuditLogger\Contracts\AuditConfigInterface;
use Nexus\AuditLogger\Contracts\AuditLogInterface;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;
use Nexus\AuditLogger\Exceptions\InvalidAuditLevelException;
use Nexus\AuditLogger\Exceptions\MissingRequiredFieldException;
use Nexus\AuditLogger\ValueObjects\AuditLevel;
use Nexus\AuditLogger\ValueObjects\RetentionPolicy;

/**
 * Core audit logging service
 * Satisfies: ARC-AUD-0004 (Business logic in service layer)
 *
 * @package Nexus\AuditLogger\Services
 */
class AuditLogManager implements AuditLogManagerInterface
{
    private AuditLogRepositoryInterface $repository;
    private AuditConfigInterface $config;
    private SensitiveDataMasker $masker;

    public function __construct(
        AuditLogRepositoryInterface $repository,
        AuditConfigInterface $config,
        ?SensitiveDataMasker $masker = null
    ) {
        $this->repository = $repository;
        $this->config = $config;
        $this->masker = $masker ?? new SensitiveDataMasker($config);
    }

    /**
     * Log an activity
     * Satisfies: BUS-AUD-0145, FUN-AUD-0185, FUN-AUD-0186
     *
     * @param string $logName Category/name of the log
     * @param string $description Human-readable description
     * @param string|null $subjectType Type of entity being acted upon
     * @param int|string|null $subjectId ID of entity being acted upon
     * @param string|null $causerType Type of entity performing action (null for system)
     * @param int|string|null $causerId ID of entity performing action (null for system)
     * @param array $properties Additional data (before/after state, metadata)
     * @param int|null $level Audit level (1-4), auto-determined if null
     * @param string|null $event Event type (created, updated, deleted, etc.)
     * @param string|null $batchUuid UUID for grouping related operations
     * @param string|null $ipAddress IP address of request
     * @param string|null $userAgent User agent string
     * @param int|string|null $tenantId Tenant ID for multi-tenancy
     * @param int|null $retentionDays Custom retention period
     * @return AuditLogInterface
     * @throws MissingRequiredFieldException
     * @throws InvalidAuditLevelException
     */
    public function log(
        string $logName,
        string $description,
        ?string $subjectType = null,
        int|string|null $subjectId = null,
        ?string $causerType = null,
        int|string|null $causerId = null,
        array $properties = [],
        ?int $level = null,
        ?string $event = null,
        ?string $batchUuid = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        int|string|null $tenantId = null,
        ?int $retentionDays = null
    ): AuditLogInterface {
        // Validate required fields per BUS-AUD-0145
        $this->validateRequiredFields($logName, $description);

        // Auto-determine level if not provided per BUS-AUD-0149
        if ($level === null) {
            $level = $subjectType 
                ? $this->config->getDefaultLevelForEntity($subjectType)
                : AuditLevel::Medium->value;
        }

        // Validate level per BUS-AUD-0146
        AuditLevel::tryFrom($level) ?? throw new InvalidAuditLevelException($level);

        // Mask sensitive data per FUN-AUD-0192
        $properties = $this->masker->maskSensitiveData($properties);

        // Use default retention if not specified per BUS-AUD-0147
        $retentionDays = $retentionDays ?? $this->config->getDefaultRetentionDays();
        $retentionPolicy = new RetentionPolicy($retentionDays);

        $createdAt = new \DateTime();
        $expiresAt = $retentionPolicy->calculateExpirationDate($createdAt);

        $data = [
            'log_name' => $logName,
            'description' => $description,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'causer_type' => $causerType,
            'causer_id' => $causerId,
            'properties' => $properties,
            'event' => $event,
            'level' => $level,
            'batch_uuid' => $batchUuid,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'tenant_id' => $tenantId,
            'retention_days' => $retentionDays,
            'created_at' => $createdAt,
            'expires_at' => $expiresAt,
        ];

        return $this->repository->create($data);
    }

    /**
     * Log a create event
     * Satisfies: FUN-AUD-0185
     */
    public function logCreated(
        string $subjectType,
        $subjectId,
        array $properties = [],
        ?string $causerType = null,
        $causerId = null,
        array $context = []
    ): AuditLogInterface {
        return $this->log(
            logName: strtolower($subjectType) . '_created',
            description: "{$subjectType} created",
            subjectType: $subjectType,
            subjectId: $subjectId,
            causerType: $causerType,
            causerId: $causerId,
            properties: ['attributes' => $properties],
            event: 'created',
            batchUuid: $context['batchUuid'] ?? null,
            ipAddress: $context['ipAddress'] ?? null,
            userAgent: $context['userAgent'] ?? null,
            tenantId: $context['tenantId'] ?? null,
            retentionDays: $context['retentionDays'] ?? null
        );
    }

    /**
     * Log an update event with before/after state
     * Satisfies: FUN-AUD-0185, FUN-AUD-0186
     */
    public function logUpdated(
        string $subjectType,
        $subjectId,
        array $oldAttributes,
        array $newAttributes,
        ?string $causerType = null,
        $causerId = null,
        array $context = []
    ): AuditLogInterface {
        return $this->log(
            logName: strtolower($subjectType) . '_updated',
            description: "{$subjectType} updated",
            subjectType: $subjectType,
            subjectId: $subjectId,
            causerType: $causerType,
            causerId: $causerId,
            properties: [
                'old' => $oldAttributes,
                'new' => $newAttributes,
                'changes' => $this->getChanges($oldAttributes, $newAttributes),
            ],
            event: 'updated',
            batchUuid: $context['batchUuid'] ?? null,
            ipAddress: $context['ipAddress'] ?? null,
            userAgent: $context['userAgent'] ?? null,
            tenantId: $context['tenantId'] ?? null,
            retentionDays: $context['retentionDays'] ?? null
        );
    }

    /**
     * Log a delete event
     * Satisfies: FUN-AUD-0185
     */
    public function logDeleted(
        string $subjectType,
        $subjectId,
        array $properties = [],
        ?string $causerType = null,
        $causerId = null,
        array $context = []
    ): AuditLogInterface {
        return $this->log(
            logName: strtolower($subjectType) . '_deleted',
            description: "{$subjectType} deleted",
            subjectType: $subjectType,
            subjectId: $subjectId,
            causerType: $causerType,
            causerId: $causerId,
            properties: ['attributes' => $properties],
            event: 'deleted',
            batchUuid: $context['batchUuid'] ?? null,
            ipAddress: $context['ipAddress'] ?? null,
            userAgent: $context['userAgent'] ?? null,
            tenantId: $context['tenantId'] ?? null,
            retentionDays: $context['retentionDays'] ?? null
        );
    }

    /**
     * Log a read/accessed event
     * Satisfies: FUN-AUD-0185
     */
    public function logAccessed(
        string $subjectType,
        $subjectId,
        ?string $causerType = null,
        $causerId = null,
        array $context = []
    ): AuditLogInterface {
        return $this->log(
            logName: strtolower($subjectType) . '_accessed',
            description: "{$subjectType} accessed",
            subjectType: $subjectType,
            subjectId: $subjectId,
            causerType: $causerType,
            causerId: $causerId,
            event: 'accessed',
            level: AuditLevel::Low->value,
            batchUuid: $context['batchUuid'] ?? null,
            ipAddress: $context['ipAddress'] ?? null,
            userAgent: $context['userAgent'] ?? null,
            tenantId: $context['tenantId'] ?? null,
            retentionDays: $context['retentionDays'] ?? null
        );
    }

    /**
     * Log a system activity
     * Satisfies: BUS-AUD-0148
     */
    public function logSystemActivity(
        string $logName,
        string $description,
        array $properties = [],
        ?int $level = null,
        array $context = []
    ): AuditLogInterface {
        return $this->log(
            logName: $logName,
            description: $description,
            causerType: null,  // System activities have null causer per BUS-AUD-0148
            causerId: null,
            properties: $properties,
            level: $level ?? AuditLevel::Medium->value,
            batchUuid: $context['batchUuid'] ?? null,
            ipAddress: $context['ipAddress'] ?? null,
            userAgent: $context['userAgent'] ?? null,
            tenantId: $context['tenantId'] ?? null,
            retentionDays: $context['retentionDays'] ?? null
        );
    }

    /**
     * Get history for a specific entity
     *
     * @param string $subjectType
     * @param int|string $subjectId
     * @param int $limit
     * @return AuditLogInterface[]
     */
    public function getEntityHistory(string $subjectType, $subjectId, int $limit = 100): array
    {
        return $this->repository->getBySubject($subjectType, $subjectId, $limit);
    }

    /**
     * Get activity history for a user/causer
     *
     * @param string $causerType
     * @param int|string $causerId
     * @param int $limit
     * @return AuditLogInterface[]
     */
    public function getUserActivity(string $causerType, $causerId, int $limit = 100): array
    {
        return $this->repository->getByCauser($causerType, $causerId, $limit);
    }

    /**
     * Validate required fields
     * Satisfies: BUS-AUD-0145
     *
     * @throws MissingRequiredFieldException
     */
    private function validateRequiredFields(string $logName, string $description): void
    {
        if (empty(trim($logName))) {
            throw new MissingRequiredFieldException('log_name');
        }

        if (empty(trim($description))) {
            throw new MissingRequiredFieldException('description');
        }
    }

    /**
     * Get array of changed fields
     */
    private function getChanges(array $old, array $new): array
    {
        $changes = [];
        foreach ($new as $key => $value) {
            if (!array_key_exists($key, $old) || $old[$key] !== $value) {
                $changes[$key] = [
                    'old' => $old[$key] ?? null,
                    'new' => $value,
                ];
            }
        }
        return $changes;
    }
}
