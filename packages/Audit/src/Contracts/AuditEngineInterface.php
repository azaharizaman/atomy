<?php

declare(strict_types=1);

namespace Nexus\Audit\Contracts;

use Nexus\Audit\ValueObjects\AuditLevel;

/**
 * Audit Engine Interface
 * 
 * Core service for creating cryptographically-verified, immutable audit records.
 * Provides dual-mode logging: synchronous for critical events, async for bulk operations.
 */
interface AuditEngineInterface
{
    /**
     * Log audit record synchronously with immediate hash chain calculation
     * 
     * Use for critical events where compliance requires the audit record
     * to exist before the business transaction completes.
     * 
     * Performance: ~5ms latency (hash calculation + database write)
     * 
     * @param string $tenantId Tenant identifier for isolation
     * @param string $recordType Type/category (e.g., 'user_role_assigned')
     * @param string $description Human-readable description
     * @param string|null $subjectType Entity type being acted upon
     * @param string|null $subjectId Entity ID being acted upon
     * @param string|null $causerType Entity type performing action
     * @param string|null $causerId Entity ID performing action
     * @param array $properties Additional data (raw, unmasked)
     * @param AuditLevel $level Severity level (default: Medium)
     * @param int|null $retentionDays Retention period (null = use default)
     * @param string|null $signedBy Optional signer for digital signature
     * 
     * @return string Record ID (ULID)
     * 
     * @throws \Nexus\Audit\Exceptions\AuditSequenceException
     * @throws \Nexus\Audit\Exceptions\HashChainException
     */
    public function logSync(
        string $tenantId,
        string $recordType,
        string $description,
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?string $causerType = null,
        ?string $causerId = null,
        array $properties = [],
        AuditLevel $level = AuditLevel::Medium,
        ?int $retentionDays = null,
        ?string $signedBy = null
    ): string;

    /**
     * Log audit record asynchronously via queue
     * 
     * Use for high-volume, non-critical events. Hash chain calculation
     * is deferred to queue worker processing.
     * 
     * Performance: <1ms (queue insertion only)
     * 
     * @param string $tenantId Tenant identifier for isolation
     * @param string $recordType Type/category
     * @param string $description Human-readable description
     * @param string|null $subjectType Entity type being acted upon
     * @param string|null $subjectId Entity ID being acted upon
     * @param string|null $causerType Entity type performing action
     * @param string|null $causerId Entity ID performing action
     * @param array $properties Additional data (raw, unmasked)
     * @param AuditLevel $level Severity level (default: Low)
     * @param int|null $retentionDays Retention period (null = use default)
     * 
     * @return string Job ID
     */
    public function logAsync(
        string $tenantId,
        string $recordType,
        string $description,
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?string $causerType = null,
        ?string $causerId = null,
        array $properties = [],
        AuditLevel $level = AuditLevel::Low,
        ?int $retentionDays = null
    ): string;

    /**
     * Get the last sequence number for a tenant
     * 
     * @param string $tenantId
     * @return int|null Null if no records exist for tenant
     */
    public function getLastSequenceNumber(string $tenantId): ?int;

    /**
     * Get the last record hash for a tenant
     * 
     * @param string $tenantId
     * @return string|null Null if no records exist for tenant
     */
    public function getLastRecordHash(string $tenantId): ?string;

    /**
     * Get audit statistics
     *
     * Returns statistics about audit records for a tenant including
     * counts by level, record type distribution, and temporal metrics.
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeImmutable|null $fromDate Start date for statistics (default: 30 days ago)
     * @param \DateTimeImmutable|null $toDate End date for statistics (default: now)
     * @return array<string, mixed> Statistics including:
     *         - total_records: int
     *         - by_level: array<string, int>
     *         - by_record_type: array<string, int>
     *         - by_causer: array<string, int>
     *         - records_per_day: array<string, int>
     */
    public function getStatistics(
        string $tenantId,
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null
    ): array;

    /**
     * Get aggregate statistics
     *
     * Returns system-wide aggregate statistics across all tenants.
     * Useful for system monitoring and capacity planning.
     *
     * @param \DateTimeImmutable|null $fromDate Start date for statistics (default: 30 days ago)
     * @param \DateTimeImmutable|null $toDate End date for statistics (default: now)
     * @return array<string, mixed> Aggregate statistics including:
     *         - total_records: int
     *         - total_tenants: int
     *         - average_records_per_tenant: float
     *         - peak_records_day: array{date: string, count: int}
     *         - storage_estimate_bytes: int
     */
    public function getAggregateStatistics(
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null
    ): array;
}
