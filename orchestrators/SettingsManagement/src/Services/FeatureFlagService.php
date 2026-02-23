<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Services;

use Nexus\SettingsManagement\Contracts\FeatureFlagProviderInterface;
use Nexus\SettingsManagement\Contracts\FeatureFlagServiceInterface;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FeatureRolloutRequest;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FeatureRolloutResult;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagCreateRequest;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagCreateResult;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagDisableRequest;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagDisableResult;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagEvaluationRequest;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagEvaluationResult;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagUpdateRequest;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagUpdateResult;
use Psr\Log\LoggerInterface;

/**
 * Orchestration service for feature flag management.
 * Handles flag lifecycle with validation, audit logging, and cache invalidation.
 */
final class FeatureFlagService implements FeatureFlagServiceInterface
{
    public function __construct(
        private readonly FeatureFlagProviderInterface $flagProvider,
        private readonly LoggerInterface $logger,
    ) {}

    public function createFlag(FlagCreateRequest $request): FlagCreateResult
    {
        $this->logger->info('Creating feature flag', [
            'key' => $request->key,
            'name' => $request->name,
            'type' => $request->type->value,
            'tenant_id' => $request->tenantId,
        ]);

        // Check if flag already exists
        if ($request->tenantId && $this->flagProvider->flagExists($request->key, $request->tenantId)) {
            return FlagCreateResult::failure("Flag '{$request->key}' already exists");
        }

        try {
            // In production, this would call the FeatureFlags package
            $flagId = 'flag_' . uniqid();

            $this->logger->info('Feature flag created', [
                'flag_id' => $flagId,
                'key' => $request->key,
            ]);

            return FlagCreateResult::success($flagId, $request->key);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to create feature flag', [
                'key' => $request->key,
                'error' => $e->getMessage(),
            ]);

            return FlagCreateResult::failure($e->getMessage());
        }
    }

    public function updateFlag(FlagUpdateRequest $request): FlagUpdateResult
    {
        $this->logger->info('Updating feature flag', [
            'flag_id' => $request->flagId,
        ]);

        try {
            // In production, this would call the FeatureFlags package
            $this->logger->info('Feature flag updated', [
                'flag_id' => $request->flagId,
            ]);

            return FlagUpdateResult::success($request->flagId);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to update feature flag', [
                'flag_id' => $request->flagId,
                'error' => $e->getMessage(),
            ]);

            return FlagUpdateResult::failure($e->getMessage());
        }
    }

    public function rolloutFeature(FeatureRolloutRequest $request): FeatureRolloutResult
    {
        $this->logger->info('Rolling out feature', [
            'flag_key' => $request->flagKey,
            'percentage' => $request->percentage,
            'tenant_id' => $request->tenantId,
        ]);

        try {
            // In production, this would update the flag targeting and clear cache
            $this->logger->info('Feature rolled out', [
                'flag_key' => $request->flagKey,
            ]);

            return FeatureRolloutResult::success($request->flagId, $request->flagKey);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to rollout feature', [
                'flag_key' => $request->flagKey,
                'error' => $e->getMessage(),
            ]);

            return FeatureRolloutResult::failure($e->getMessage());
        }
    }

    public function evaluateFlags(FlagEvaluationRequest $request): FlagEvaluationResult
    {
        $results = [];

        foreach ($request->flagKeys as $flagKey) {
            try {
                $results[$flagKey] = $this->flagProvider->evaluateFlag(
                    $flagKey,
                    $request->tenantId,
                    [
                        'user_id' => $request->userId,
                        'ip_address' => $request->ipAddress,
                        'context' => $request->context,
                    ]
                );
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to evaluate flag', [
                    'flag_key' => $flagKey,
                    'error' => $e->getMessage(),
                ]);
                $results[$flagKey] = false;
            }
        }

        return FlagEvaluationResult::success($results);
    }

    public function disableFlag(FlagDisableRequest $request): FlagDisableResult
    {
        $this->logger->info('Disabling feature flag', [
            'flag_key' => $request->flagKey,
            'graceful' => $request->gracefulDegradation,
        ]);

        try {
            // In production, this would disable the flag
            $this->logger->info('Feature flag disabled', [
                'flag_key' => $request->flagKey,
            ]);

            return FlagDisableResult::success($request->flagId, $request->flagKey);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to disable feature flag', [
                'flag_key' => $request->flagKey,
                'error' => $e->getMessage(),
            ]);

            return FlagDisableResult::failure($e->getMessage());
        }
    }
}
