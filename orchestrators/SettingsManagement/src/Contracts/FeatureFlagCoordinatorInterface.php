<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Contracts;

use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagCreateRequest;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagCreateResult;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagUpdateRequest;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagUpdateResult;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FeatureRolloutRequest;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FeatureRolloutResult;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagDisableRequest;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagDisableResult;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagEvaluationRequest;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagEvaluationResult;

/**
 * Coordinator interface for managing feature flags.
 */
interface FeatureFlagCoordinatorInterface extends SettingsCoordinatorInterfaceBase
{
    /**
     * Create a new feature flag.
     */
    public function createFlag(FlagCreateRequest $request): FlagCreateResult;

    /**
     * Update an existing feature flag.
     */
    public function updateFlag(FlagUpdateRequest $request): FlagUpdateResult;

    /**
     * Rollout a feature to users.
     */
    public function rolloutFeature(FeatureRolloutRequest $request): FeatureRolloutResult;

    /**
     * Evaluate feature flags for a context.
     */
    public function evaluateFlags(FlagEvaluationRequest $request): FlagEvaluationResult;

    /**
     * Disable a feature flag (kill switch).
     */
    public function disableFlag(FlagDisableRequest $request): FlagDisableResult;

    /**
     * Get all feature flags for a tenant.
     */
    public function getAllFlags(string $tenantId): array;

    /**
     * Get feature flag by key.
     */
    public function getFlagByKey(string $flagKey, string $tenantId): ?array;
}
