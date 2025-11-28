<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Enums;

/**
 * Audit action types for feature flag compliance tracking.
 *
 * Tracks all state-changing operations on feature flags for regulatory compliance.
 * Used with FlagAuditChangeInterface to record changes via Nexus\AuditLogger.
 */
enum AuditAction: string
{
    /**
     * Flag was created.
     */
    case CREATED = 'flag_created';

    /**
     * Flag was updated (any field change).
     */
    case UPDATED = 'flag_updated';

    /**
     * Flag was deleted.
     */
    case DELETED = 'flag_deleted';

    /**
     * Flag enabled state was changed.
     */
    case ENABLED_CHANGED = 'flag_enabled_changed';

    /**
     * Flag strategy was changed.
     */
    case STRATEGY_CHANGED = 'flag_strategy_changed';

    /**
     * Flag override was set or changed.
     */
    case OVERRIDE_CHANGED = 'flag_override_changed';

    /**
     * Flag was force-enabled (FORCE_ON override applied).
     */
    case FORCE_ENABLED = 'flag_force_enabled';

    /**
     * Flag was force-disabled (FORCE_OFF override applied / kill switch).
     */
    case FORCE_DISABLED = 'flag_force_disabled';

    /**
     * Flag override was removed (returned to normal evaluation).
     */
    case OVERRIDE_CLEARED = 'flag_override_cleared';

    /**
     * Flag rollout percentage was changed.
     */
    case ROLLOUT_CHANGED = 'flag_rollout_changed';

    /**
     * Flag target list was changed (tenant list or user list).
     */
    case TARGET_LIST_CHANGED = 'flag_target_list_changed';

    /**
     * Get human-readable description of the action.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::CREATED => 'Feature flag created',
            self::UPDATED => 'Feature flag updated',
            self::DELETED => 'Feature flag deleted',
            self::ENABLED_CHANGED => 'Feature flag enabled state changed',
            self::STRATEGY_CHANGED => 'Feature flag evaluation strategy changed',
            self::OVERRIDE_CHANGED => 'Feature flag override changed',
            self::FORCE_ENABLED => 'Feature flag force-enabled',
            self::FORCE_DISABLED => 'Feature flag force-disabled (kill switch)',
            self::OVERRIDE_CLEARED => 'Feature flag override cleared',
            self::ROLLOUT_CHANGED => 'Feature flag rollout percentage changed',
            self::TARGET_LIST_CHANGED => 'Feature flag target list changed',
        };
    }

    /**
     * Check if this action is a critical compliance event.
     *
     * Critical events require additional audit trail retention.
     */
    public function isCritical(): bool
    {
        return match ($this) {
            self::FORCE_DISABLED,
            self::FORCE_ENABLED,
            self::DELETED,
            self::OVERRIDE_CHANGED => true,
            default => false,
        };
    }
}
