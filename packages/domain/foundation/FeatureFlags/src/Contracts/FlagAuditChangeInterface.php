<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Contracts;

use Nexus\FeatureFlags\Enums\AuditAction;

/**
 * Interface for auditing feature flag changes (write operations).
 *
 * This interface defines the contract for logging state-changing operations
 * on feature flags for compliance and audit trail purposes.
 *
 * Application layer should implement this using Nexus\AuditLogger for:
 * - Timeline/feed views of flag changes
 * - User action tracking
 * - Compliance reporting (SOX, GDPR, etc.)
 *
 * @example
 * // Application layer implementation using Nexus\AuditLogger
 * final readonly class FeatureFlagAuditLogger implements FlagAuditChangeInterface
 * {
 *     public function __construct(
 *         private AuditLogRepositoryInterface $auditLogger,
 *         private TenantContextInterface $tenantContext
 *     ) {}
 *
 *     public function recordChange(
 *         string $flagName,
 *         AuditAction $action,
 *         ?string $userId,
 *         ?array $before,
 *         ?array $after,
 *         array $metadata
 *     ): void {
 *         $this->auditLogger->create([
 *             'log_name' => 'feature_flags',
 *             'subject_type' => 'feature_flag',
 *             'subject_id' => $flagName,
 *             'causer_type' => 'user',
 *             'causer_id' => $userId,
 *             'event' => $action->value,
 *             'description' => $action->getDescription(),
 *             'properties' => [
 *                 'before' => $before,
 *                 'after' => $after,
 *                 ...$metadata,
 *             ],
 *             'tenant_id' => $this->tenantContext->getCurrentTenantId(),
 *         ]);
 *     }
 * }
 */
interface FlagAuditChangeInterface
{
    /**
     * Record a feature flag change for audit trail.
     *
     * @param string $flagName The name of the flag that was changed
     * @param AuditAction $action The type of change action
     * @param string|null $userId The ID of the user who made the change (null for system changes)
     * @param array<string, mixed>|null $before The state before the change (null for create)
     * @param array<string, mixed>|null $after The state after the change (null for delete)
     * @param array<string, mixed> $metadata Additional metadata (reason, ip_address, etc.)
     * @return void
     *
     * @example
     * // Record flag creation
     * $auditChange->recordChange(
     *     flagName: 'new_checkout_flow',
     *     action: AuditAction::CREATED,
     *     userId: 'user-123',
     *     before: null,
     *     after: [
     *         'name' => 'new_checkout_flow',
     *         'enabled' => false,
     *         'strategy' => 'system_wide',
     *     ],
     *     metadata: ['reason' => 'Feature rollout preparation']
     * );
     *
     * // Record kill switch activation
     * $auditChange->recordChange(
     *     flagName: 'payment_v2',
     *     action: AuditAction::FORCE_DISABLED,
     *     userId: 'user-456',
     *     before: ['override' => null],
     *     after: ['override' => 'force_off'],
     *     metadata: [
     *         'reason' => 'Production incident INC-2024-001',
     *         'severity' => 'critical',
     *     ]
     * );
     */
    public function recordChange(
        string $flagName,
        AuditAction $action,
        ?string $userId,
        ?array $before,
        ?array $after,
        array $metadata = []
    ): void;

    /**
     * Record a batch of changes in a single audit entry.
     *
     * Used for bulk operations where multiple flags are changed together.
     *
     * @param AuditAction $action The type of change action
     * @param string|null $userId The ID of the user who made the changes
     * @param array<string, array{before: array<string, mixed>|null, after: array<string, mixed>|null}> $changes
     *        Map of flag name => [before state, after state]
     * @param array<string, mixed> $metadata Additional metadata
     * @return void
     *
     * @example
     * $auditChange->recordBatchChange(
     *     action: AuditAction::FORCE_DISABLED,
     *     userId: 'user-456',
     *     changes: [
     *         'feature_a' => ['before' => [...], 'after' => [...]],
     *         'feature_b' => ['before' => [...], 'after' => [...]],
     *     ],
     *     metadata: ['reason' => 'Emergency rollback of release v2.5']
     * );
     */
    public function recordBatchChange(
        AuditAction $action,
        ?string $userId,
        array $changes,
        array $metadata = []
    ): void;
}
