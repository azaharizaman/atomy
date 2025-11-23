<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Enums;

/**
 * Feature flag evaluation strategies.
 *
 * Defines how a flag determines whether it should be enabled for a given context.
 */
enum FlagStrategy: string
{
    /**
     * Enabled/disabled globally for all users.
     *
     * No context evaluation required.
     * Value: null
     */
    case SYSTEM_WIDE = 'system_wide';

    /**
     * Enabled for a percentage of users based on stable identifier.
     *
     * Uses consistent hashing to determine if a user falls within the rollout percentage.
     * Requires context with stable identifier (userId, sessionId, or tenantId).
     * Value: int (0-100)
     *
     * @example
     * // Enable for 25% of users
     * strategy: PERCENTAGE_ROLLOUT
     * value: 25
     */
    case PERCENTAGE_ROLLOUT = 'percentage_rollout';

    /**
     * Enabled only for specific tenants.
     *
     * Requires context with tenantId.
     * Value: array of tenant IDs
     *
     * @example
     * strategy: TENANT_LIST
     * value: ['tenant-abc', 'tenant-xyz']
     */
    case TENANT_LIST = 'tenant_list';

    /**
     * Enabled only for specific users.
     *
     * Requires context with userId.
     * Value: array of user IDs
     *
     * @example
     * strategy: USER_LIST
     * value: ['user-alice', 'user-bob']
     */
    case USER_LIST = 'user_list';

    /**
     * Custom evaluation logic via CustomEvaluatorInterface implementation.
     *
     * Value: class-string of CustomEvaluatorInterface implementation
     *
     * @example
     * strategy: CUSTOM
     * value: 'App\\FeatureFlags\\PremiumMalaysianUsersEvaluator'
     */
    case CUSTOM = 'custom';
}
