<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Services;

use Nexus\FeatureFlags\Contracts\FlagAuditChangeInterface;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\FeatureFlags\Enums\AuditAction;
use Nexus\FeatureFlags\Enums\FlagOverride;

/**
 * Decorator for FlagRepositoryInterface that automatically records audit trail.
 *
 * Wraps any FlagRepositoryInterface implementation to add audit logging
 * for all write operations (save, delete) via FlagAuditChangeInterface.
 *
 * This enables compliance tracking for critical feature flags by:
 * - Recording all changes with before/after state
 * - Tracking who made the change (via userId in metadata)
 * - Supporting batch operation tracking
 *
 * @example
 * // Create auditable repository
 * $auditableRepo = new AuditableFlagRepository(
 *     $baseRepository,
 *     $auditChangeLogger,
 *     userId: 'user-123'
 * );
 *
 * // All save/delete operations are now audited
 * $auditableRepo->save($flag); // Automatically logs the change
 */
final class AuditableFlagRepository implements FlagRepositoryInterface
{
    private ?string $userId;
    private array $defaultMetadata;
    private ?string $currentTenantId = null;

    /**
     * @param FlagRepositoryInterface $repository The underlying repository
     * @param FlagAuditChangeInterface $auditChange The audit change logger
     * @param string|null $userId Current user ID for audit trail
     * @param array<string, mixed> $defaultMetadata Default metadata for all audit records
     */
    public function __construct(
        private readonly FlagRepositoryInterface $repository,
        private readonly FlagAuditChangeInterface $auditChange,
        ?string $userId = null,
        array $defaultMetadata = []
    ) {
        $this->userId = $userId;
        $this->defaultMetadata = $defaultMetadata;
    }

    /**
     * Set the current user ID for audit trail.
     *
     * @param string|null $userId
     * @return self
     */
    public function withUserId(?string $userId): self
    {
        $clone = clone $this;
        $clone->userId = $userId;
        return $clone;
    }

    /**
     * Set the current tenant ID for audit context.
     *
     * @param string|null $tenantId
     * @return self
     */
    public function withTenantId(?string $tenantId): self
    {
        $clone = clone $this;
        $clone->currentTenantId = $tenantId;
        return $clone;
    }

    /**
     * Set additional metadata for audit records.
     *
     * @param array<string, mixed> $metadata
     * @return self
     */
    public function withMetadata(array $metadata): self
    {
        $clone = clone $this;
        $clone->defaultMetadata = array_merge($this->defaultMetadata, $metadata);
        return $clone;
    }

    public function find(string $name, ?string $tenantId = null): ?FlagDefinitionInterface
    {
        return $this->repository->find($name, $tenantId);
    }

    public function findMany(array $names, ?string $tenantId = null): array
    {
        return $this->repository->findMany($names, $tenantId);
    }

    /**
     * Save a flag with automatic audit logging.
     *
     * Note: To properly track tenant context, use saveForTenant() or call
     * withTenantId() before save() for tenant-scoped flags.
     *
     * @param FlagDefinitionInterface $flag
     * @return void
     */
    public function save(FlagDefinitionInterface $flag): void
    {
        $this->saveForTenant($flag, $this->currentTenantId);
    }

    /**
     * Save a flag for a specific tenant with audit logging.
     *
     * @param FlagDefinitionInterface $flag
     * @param string|null $tenantId
     * @return void
     */
    public function saveForTenant(FlagDefinitionInterface $flag, ?string $tenantId = null): void
    {
        // Get existing flag for before state
        $existing = $this->repository->find($flag->getName(), $tenantId);

        // Determine action type
        $action = $this->determineAction($existing, $flag);

        // Get before/after state
        $before = $existing ? $this->flagToArray($existing) : null;
        $after = $this->flagToArray($flag);

        // Save the flag with proper tenant context
        $this->repository->saveForTenant($flag, $tenantId);

        // Record the change
        $this->auditChange->recordChange(
            flagName: $flag->getName(),
            action: $action,
            userId: $this->userId,
            before: $before,
            after: $after,
            metadata: array_merge($this->defaultMetadata, [
                'tenant_id' => $tenantId,
            ])
        );
    }

    public function delete(string $name, ?string $tenantId = null): void
    {
        // Get existing flag for before state
        $existing = $this->repository->find($name, $tenantId);
        $before = $existing ? $this->flagToArray($existing) : null;

        // Delete the flag
        $this->repository->delete($name, $tenantId);

        // Record the deletion
        $this->auditChange->recordChange(
            flagName: $name,
            action: AuditAction::DELETED,
            userId: $this->userId,
            before: $before,
            after: null,
            metadata: array_merge($this->defaultMetadata, [
                'tenant_id' => $tenantId,
            ])
        );
    }

    public function all(?string $tenantId = null): array
    {
        return $this->repository->all($tenantId);
    }

    /**
     * Determine the appropriate audit action based on changes.
     *
     * @param FlagDefinitionInterface|null $existing
     * @param FlagDefinitionInterface $new
     * @return AuditAction
     */
    private function determineAction(?FlagDefinitionInterface $existing, FlagDefinitionInterface $new): AuditAction
    {
        // New flag creation
        if ($existing === null) {
            return AuditAction::CREATED;
        }

        // Check for override changes (highest priority)
        $existingOverride = $existing->getOverride();
        $newOverride = $new->getOverride();

        if ($existingOverride !== $newOverride) {
            if ($newOverride === FlagOverride::FORCE_OFF) {
                return AuditAction::FORCE_DISABLED;
            }
            if ($newOverride === FlagOverride::FORCE_ON) {
                return AuditAction::FORCE_ENABLED;
            }
            if ($newOverride === null && $existingOverride !== null) {
                return AuditAction::OVERRIDE_CLEARED;
            }
            return AuditAction::OVERRIDE_CHANGED;
        }

        // Check for enabled state change
        if ($existing->isEnabled() !== $new->isEnabled()) {
            return AuditAction::ENABLED_CHANGED;
        }

        // Check for strategy change
        if ($existing->getStrategy() !== $new->getStrategy()) {
            return AuditAction::STRATEGY_CHANGED;
        }

        // Check for value changes based on strategy
        if ($existing->getValue() !== $new->getValue()) {
            return match ($new->getStrategy()->value) {
                'percentage_rollout' => AuditAction::ROLLOUT_CHANGED,
                'tenant_list', 'user_list' => AuditAction::TARGET_LIST_CHANGED,
                default => AuditAction::UPDATED,
            };
        }

        // Generic update
        return AuditAction::UPDATED;
    }

    /**
     * Convert a flag to array representation for audit.
     *
     * @param FlagDefinitionInterface $flag
     * @return array<string, mixed>
     */
    private function flagToArray(FlagDefinitionInterface $flag): array
    {
        return [
            'name' => $flag->getName(),
            'enabled' => $flag->isEnabled(),
            'strategy' => $flag->getStrategy()->value,
            'value' => $flag->getValue(),
            'override' => $flag->getOverride()?->value,
            'metadata' => $flag->getMetadata(),
        ];
    }
}
