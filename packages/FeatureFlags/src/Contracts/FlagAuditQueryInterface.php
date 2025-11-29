<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Contracts;

use DateTimeImmutable;
use Nexus\FeatureFlags\Enums\AuditAction;

/**
 * Interface for querying feature flag audit history (read operations).
 *
 * This interface defines the contract for retrieving historical audit records
 * of feature flag changes for compliance, forensics, and state reconstruction.
 *
 * Application layer should implement this using Nexus\EventStream for:
 * - Temporal queries ("What was the flag state on 2024-10-15?")
 * - State reconstruction for compliance audits
 * - Full audit trail with replay capability
 *
 * @example
 * // Application layer implementation using Nexus\EventStream
 * final readonly class FeatureFlagAuditQuery implements FlagAuditQueryInterface
 * {
 *     public function __construct(
 *         private EventStoreInterface $eventStore
 *     ) {}
 *
 *     public function getHistory(string $flagName, ?string $tenantId, int $limit): array
 *     {
 *         return $this->eventStore->query(
 *             filters: [
 *                 'aggregate_id' => ['operator' => '=', 'value' => "flag:{$flagName}"],
 *             ],
 *             inFilters: [],
 *             orderByField: 'occurred_at',
 *             orderDirection: 'desc',
 *             limit: $limit
 *         );
 *     }
 * }
 */
interface FlagAuditQueryInterface
{
    /**
     * Get the complete audit history for a specific flag.
     *
     * @param string $flagName The flag name to query
     * @param string|null $tenantId Optional tenant scope (null for global)
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array<FlagAuditRecordInterface> Array of audit records, newest first
     *
     * @example
     * $history = $auditQuery->getHistory('payment_v2', 'tenant-123', 50);
     * foreach ($history as $record) {
     *     echo sprintf(
     *         "[%s] %s by %s: %s\n",
     *         $record->getOccurredAt()->format('Y-m-d H:i:s'),
     *         $record->getAction()->value,
     *         $record->getUserId() ?? 'SYSTEM',
     *         $record->getReason() ?? 'No reason provided'
     *     );
     * }
     */
    public function getHistory(
        string $flagName,
        ?string $tenantId = null,
        int $limit = 100,
        int $offset = 0
    ): array;

    /**
     * Get the state of a flag at a specific point in time.
     *
     * Reconstructs the flag state by replaying events up to the given timestamp.
     * Essential for compliance audits ("What was enabled on date X?").
     *
     * @param string $flagName The flag name to query
     * @param DateTimeImmutable $timestamp The point in time to query
     * @param string|null $tenantId Optional tenant scope
     * @return array<string, mixed>|null The flag state at that time, or null if flag didn't exist
     *
     * @example
     * // Compliance audit: What was the flag state during incident?
     * $stateAtIncident = $auditQuery->getStateAt(
     *     'payment_v2',
     *     new DateTimeImmutable('2024-11-15 14:30:00'),
     *     'tenant-123'
     * );
     */
    public function getStateAt(
        string $flagName,
        DateTimeImmutable $timestamp,
        ?string $tenantId = null
    ): ?array;

    /**
     * Get all changes within a date range.
     *
     * @param DateTimeImmutable $from Start of date range (inclusive)
     * @param DateTimeImmutable $to End of date range (inclusive)
     * @param string|null $tenantId Optional tenant scope
     * @param string|null $flagName Optional filter by specific flag
     * @param AuditAction|null $action Optional filter by action type
     * @param int $limit Maximum number of records to return
     * @return array<FlagAuditRecordInterface> Array of audit records
     *
     * @example
     * // Get all changes in the last 7 days
     * $changes = $auditQuery->getChangesBetween(
     *     new DateTimeImmutable('-7 days'),
     *     new DateTimeImmutable('now'),
     *     'tenant-123'
     * );
     */
    public function getChangesBetween(
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        ?string $tenantId = null,
        ?string $flagName = null,
        ?AuditAction $action = null,
        int $limit = 1000
    ): array;

    /**
     * Get all changes made by a specific user.
     *
     * @param string $userId The user ID to query
     * @param string|null $tenantId Optional tenant scope
     * @param int $limit Maximum number of records to return
     * @return array<FlagAuditRecordInterface> Array of audit records
     *
     * @example
     * // Audit trail: What did user X change?
     * $userChanges = $auditQuery->getChangesByUser('user-456', 'tenant-123', 100);
     */
    public function getChangesByUser(
        string $userId,
        ?string $tenantId = null,
        int $limit = 100
    ): array;

    /**
     * Get all critical changes (force enables, kill switches, deletions).
     *
     * Returns only actions marked as critical for compliance review.
     *
     * @param string|null $tenantId Optional tenant scope
     * @param DateTimeImmutable|null $since Only changes after this timestamp
     * @param int $limit Maximum number of records to return
     * @return array<FlagAuditRecordInterface> Array of critical audit records
     *
     * @example
     * // Compliance review: All critical changes this month
     * $criticalChanges = $auditQuery->getCriticalChanges(
     *     'tenant-123',
     *     new DateTimeImmutable('first day of this month')
     * );
     */
    public function getCriticalChanges(
        ?string $tenantId = null,
        ?DateTimeImmutable $since = null,
        int $limit = 500
    ): array;

    /**
     * Count total audit records for a flag.
     *
     * @param string $flagName The flag name to query
     * @param string|null $tenantId Optional tenant scope
     * @return int Total count of audit records
     */
    public function countHistory(string $flagName, ?string $tenantId = null): int;
}
