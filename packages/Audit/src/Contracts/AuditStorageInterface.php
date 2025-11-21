<?php

declare(strict_types=1);

namespace Nexus\Audit\Contracts;

/**
 * Audit Storage Interface
 * 
 * Append-only persistence layer for audit records.
 * NO UPDATE OR DELETE METHODS - immutability enforced at contract level.
 * 
 * Satisfies: SEC-AUD-0486 (Immutable audit logs)
 */
interface AuditStorageInterface
{
    /**
     * Store new audit record (append-only)
     * 
     * @param array $data Record data
     * @return AuditRecordInterface Created record
     * 
     * @throws \Nexus\Audit\Exceptions\AuditStorageException
     */
    public function store(array $data): AuditRecordInterface;

    /**
     * Find record by ID
     * 
     * Automatically verifies hash on retrieval.
     * 
     * @param string $id Record ID
     * @return AuditRecordInterface|null
     * 
     * @throws \Nexus\Audit\Exceptions\AuditTamperedException If hash mismatch
     */
    public function findById(string $id): ?AuditRecordInterface;

    /**
     * Get records for specific entity (subject)
     * 
     * @param string $tenantId Tenant ID
     * @param string $subjectType Entity type
     * @param string $subjectId Entity ID
     * @param int $limit Maximum records
     * @return array<AuditRecordInterface>
     */
    public function findBySubject(
        string $tenantId,
        string $subjectType,
        string $subjectId,
        int $limit = 100
    ): array;

    /**
     * Get records by causer (who performed action)
     * 
     * @param string $tenantId Tenant ID
     * @param string $causerType Entity type
     * @param string $causerId Entity ID
     * @param int $limit Maximum records
     * @return array<AuditRecordInterface>
     */
    public function findByCauser(
        string $tenantId,
        string $causerType,
        string $causerId,
        int $limit = 100
    ): array;

    /**
     * Get records by tenant in sequence order
     * 
     * @param string $tenantId Tenant ID
     * @param int $fromSequence Starting sequence number
     * @param int $limit Maximum records
     * @return array<AuditRecordInterface>
     */
    public function findByTenantSequence(
        string $tenantId,
        int $fromSequence = 1,
        int $limit = 100
    ): array;

    /**
     * Get last record for tenant (highest sequence number)
     * 
     * @param string $tenantId
     * @return AuditRecordInterface|null
     */
    public function getLastRecord(string $tenantId): ?AuditRecordInterface;

    /**
     * Get expired records for purging
     * 
     * @param \DateTimeImmutable|null $beforeDate Expiration cutoff
     * @param int $limit Maximum records
     * @return array<AuditRecordInterface>
     */
    public function findExpired(
        ?\DateTimeImmutable $beforeDate = null,
        int $limit = 1000
    ): array;

    /**
     * Physically delete expired records (only method allowing deletion)
     * 
     * @param array $ids Array of record IDs to delete
     * @return int Number of records deleted
     */
    public function deleteExpired(array $ids): int;
}
