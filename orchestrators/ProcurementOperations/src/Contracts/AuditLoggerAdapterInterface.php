<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

/**
 * Adapter interface for audit logging integration.
 *
 * This interface defines what the ProcurementOperations orchestrator needs
 * from an audit logging service. Consuming applications must provide a
 * concrete implementation that adapts their chosen audit logger.
 *
 * @package Nexus\ProcurementOperations\Contracts
 */
interface AuditLoggerAdapterInterface
{
    /**
     * Log an activity.
     *
     * @param string $logName Category/name of the log (e.g., 'procurement', 'compliance')
     * @param string $description Human-readable description
     * @param string|null $subjectType Type of entity being acted upon (e.g., 'purchase_order')
     * @param string|null $subjectId ID of entity being acted upon
     * @param array<string, mixed> $properties Additional contextual data
     * @param string|null $event Event type (e.g., 'created', 'approved', 'rejected')
     * @param string|null $tenantId Tenant ID for multi-tenancy
     */
    public function log(
        string $logName,
        string $description,
        ?string $subjectType = null,
        ?string $subjectId = null,
        array $properties = [],
        ?string $event = null,
        ?string $tenantId = null,
    ): void;

    /**
     * Search audit logs with filters.
     *
     * @param array<string, mixed> $filters Search filters (e.g., log_name, subject_type, date range)
     * @param int $limit Maximum results to return
     * @param int $offset Offset for pagination
     * @param string|null $tenantId Tenant ID for filtering
     * @return array<array<string, mixed>> Array of audit log entries
     */
    public function search(
        array $filters = [],
        int $limit = 100,
        int $offset = 0,
        ?string $tenantId = null,
    ): array;

    /**
     * Get audit logs for a specific subject.
     *
     * @param string $subjectType Type of entity
     * @param string $subjectId ID of entity
     * @param string|null $tenantId Tenant ID for filtering
     * @return array<array<string, mixed>> Array of audit log entries
     */
    public function getLogsForSubject(
        string $subjectType,
        string $subjectId,
        ?string $tenantId = null,
    ): array;

    /**
     * Get audit logs within a date range.
     *
     * @param \DateTimeImmutable $startDate Start of date range
     * @param \DateTimeImmutable $endDate End of date range
     * @param array<string, mixed> $filters Additional filters
     * @param string|null $tenantId Tenant ID for filtering
     * @return array<array<string, mixed>> Array of audit log entries
     */
    public function getLogsByDateRange(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        array $filters = [],
        ?string $tenantId = null,
    ): array;

    /**
     * Count audit logs matching filters.
     *
     * @param array<string, mixed> $filters Search filters
     * @param string|null $tenantId Tenant ID for filtering
     * @return int Count of matching logs
     */
    public function count(
        array $filters = [],
        ?string $tenantId = null,
    ): int;
}
