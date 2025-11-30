<?php

declare(strict_types=1);

namespace Nexus\AuditLogger\Contracts;

/**
 * Interface AuditLogRepositoryInterface
 *
 * Defines persistence operations for audit logs.
 * Satisfies: ARC-AUD-0003
 *
 * @package Nexus\AuditLogger\Contracts
 */
interface AuditLogRepositoryInterface
{
    /**
     * Create a new audit log entry
     *
     * @param array $data Audit log data
     * @return AuditLogInterface
     */
    public function create(array $data): AuditLogInterface;

    /**
     * Find an audit log by ID
     *
     * @param int|string $id
     * @return AuditLogInterface|null
     */
    public function findById($id): ?AuditLogInterface;

    /**
     * Search audit logs with filters
     * Satisfies: FUN-AUD-0189, FUN-AUD-0190
     *
     * @param array $filters [
     *     'log_name' => string,
     *     'description' => string (full-text search),
     *     'subject_type' => string,
     *     'subject_id' => int|string,
     *     'causer_type' => string,
     *     'causer_id' => int|string,
     *     'event' => string,
     *     'level' => int,
     *     'tenant_id' => int|string,
     *     'batch_uuid' => string,
     *     'date_from' => string|DateTimeInterface,
     *     'date_to' => string|DateTimeInterface,
     *     'search' => string (full-text search across all fields)
     * ]
     * @param int $page
     * @param int $perPage
     * @param string $sortBy
     * @param string $sortDirection
     * @return array ['data' => AuditLogInterface[], 'total' => int]
     */
    public function search(
        array $filters = [],
        int $page = 1,
        int $perPage = 50,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): array;

    /**
     * Get audit logs for a specific subject entity
     *
     * @param string $subjectType
     * @param int|string $subjectId
     * @param int $limit
     * @return AuditLogInterface[]
     */
    public function getBySubject(string $subjectType, $subjectId, int $limit = 100): array;

    /**
     * Get audit logs by causer (user who performed actions)
     *
     * @param string $causerType
     * @param int|string $causerId
     * @param int $limit
     * @return AuditLogInterface[]
     */
    public function getByCauser(string $causerType, $causerId, int $limit = 100): array;

    /**
     * Get audit logs by batch UUID
     * Satisfies: BUS-AUD-0150
     *
     * @param string $batchUuid
     * @return AuditLogInterface[]
     */
    public function getByBatchUuid(string $batchUuid): array;

    /**
     * Get audit logs by level
     * Satisfies: FUN-AUD-0195
     *
     * @param int $level 1=Low, 2=Medium, 3=High, 4=Critical
     * @param int $limit
     * @return AuditLogInterface[]
     */
    public function getByLevel(int $level, int $limit = 100): array;

    /**
     * Get audit logs by tenant ID
     * Satisfies: FUN-AUD-0188
     *
     * @param int|string $tenantId
     * @param int $limit
     * @return AuditLogInterface[]
     */
    public function getByTenant($tenantId, int $limit = 100): array;

    /**
     * Get expired audit logs for purging
     * Satisfies: BUS-AUD-0151
     *
     * @param \DateTimeInterface|null $beforeDate If null, uses current date
     * @param int $limit
     * @return AuditLogInterface[]
     */
    public function getExpired(?\DateTimeInterface $beforeDate = null, int $limit = 1000): array;

    /**
     * Delete expired audit logs
     * Satisfies: BUS-AUD-0151, FUN-AUD-0194
     *
     * @param \DateTimeInterface|null $beforeDate If null, uses current date
     * @return int Number of deleted records
     */
    public function deleteExpired(?\DateTimeInterface $beforeDate = null): int;

    /**
     * Delete audit logs by IDs
     *
     * @param array $ids
     * @return int Number of deleted records
     */
    public function deleteByIds(array $ids): int;

    /**
     * Get activity statistics
     * Satisfies: FUN-AUD-0199
     *
     * @param array $filters Same as search filters
     * @return array [
     *     'total_count' => int,
     *     'by_log_name' => ['log_name' => count],
     *     'by_level' => ['level' => count],
     *     'by_event' => ['event' => count],
     *     'by_date' => ['date' => count]
     * ]
     */
    public function getStatistics(array $filters = []): array;

    /**
     * Export audit logs to array format
     * Satisfies: FUN-AUD-0191
     *
     * @param array $filters Same as search filters
     * @param int $limit Maximum records to export
     * @return array
     */
    public function exportToArray(array $filters = [], int $limit = 10000): array;
}
