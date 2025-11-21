<?php

declare(strict_types=1);

namespace Nexus\AuditLogger\Contracts;

/**
 * Audit Log Manager Interface
 *
 * Main service for logging audit events.
 */
interface AuditLogManagerInterface
{
    /**
     * Log an audit activity
     *
     * @param string $logName Category/name of the log
     * @param string $description Human-readable description
     * @param string|null $subjectType Type of entity being acted upon
     * @param int|string|null $subjectId ID of entity being acted upon
     * @param string|null $causerType Type of entity performing action
     * @param int|string|null $causerId ID of entity performing action
     * @param array $properties Additional data
     * @param int|null $level Audit level (1-4)
     * @param string|null $event Event type
     * @param string|null $batchUuid UUID for grouping operations
     * @param string|null $ipAddress IP address
     * @param string|null $userAgent User agent string
     * @param int|string|null $tenantId Tenant ID
     * @param int|null $retentionDays Custom retention period
     * @return AuditLogInterface
     * @throws \Nexus\AuditLogger\Exceptions\MissingRequiredFieldException
     * @throws \Nexus\AuditLogger\Exceptions\InvalidAuditLevelException
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
    ): AuditLogInterface;
}
