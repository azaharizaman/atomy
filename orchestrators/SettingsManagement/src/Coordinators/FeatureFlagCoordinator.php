<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Coordinators;

use Nexus\SettingsManagement\Contracts\FeatureFlagCoordinatorInterface;
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
use Nexus\SettingsManagement\Rules\FeatureFlagConflictRule;
use Nexus\SettingsManagement\Rules\FeatureFlagTargetingRule;

/**
 * Coordinator for managing feature flags.
 * Orchestrates validation rules, data providers, and services.
 */
final class FeatureFlagCoordinator implements FeatureFlagCoordinatorInterface
{
    public function __construct(
        private readonly FeatureFlagProviderInterface $flagProvider,
        private readonly FeatureFlagServiceInterface $flagService,
    ) {}

    public function getName(): string
    {
        return 'FeatureFlagCoordinator';
    }

    public function hasRequiredData(string $tenantId): bool
    {
        $flags = $this->flagProvider->getAllFlags($tenantId);
        return !empty($flags);
    }

    public function createFlag(FlagCreateRequest $request): FlagCreateResult
    {
        // Check if flag already exists
        if ($request->tenantId && $this->flagProvider->flagExists($request->key, $request->tenantId)) {
            return FlagCreateResult::failure("Flag '{$request->key}' already exists");
        }

        return $this->flagService->createFlag($request);
    }

    public function updateFlag(FlagUpdateRequest $request): FlagUpdateResult
    {
        return $this->flagService->updateFlag($request);
    }

    public function rolloutFeature(FeatureRolloutRequest $request): FeatureRolloutResult
    {
        // Validate rollout
        $validationResult = $this->validateRollout($request);
        if (!$validationResult->passed) {
            return FeatureRolloutResult::failure($validationResult->error ?? 'Validation failed');
        }

        return $this->flagService->rolloutFeature($request);
    }

    public function evaluateFlags(FlagEvaluationRequest $request): FlagEvaluationResult
    {
        return $this->flagService->evaluateFlags($request);
    }

    public function disableFlag(FlagDisableRequest $request): FlagDisableResult
    {
        return $this->flagService->disableFlag($request);
    }

    public function getAllFlags(string $tenantId): array
    {
        return $this->flagProvider->getAllFlags($tenantId);
    }

    public function getFlagByKey(string $flagKey, string $tenantId): ?array
    {
        return $this->flagProvider->getFlag($flagKey, $tenantId);
    }

    private function validateRollout(FeatureRolloutRequest $request): \Nexus\SettingsManagement\Contracts\RuleValidationResult
    {
        $errors = [];

        // Get current flags for conflict check
        $currentFlags = [];
        if ($request->tenantId) {
            $flags = $this->flagProvider->getAllFlags($request->tenantId);
            foreach ($flags as $flag) {
                $currentFlags[$flag['key'] ?? ''] = $flag['enabled'] ?? false;
            }
        }

        // Check conflicts
        $conflictRule = new FeatureFlagConflictRule();
        $conflictResult = $conflictRule->evaluate([
            'flagKey' => $request->flagKey,
            'tenantId' => $request->tenantId,
            'targeting' => [
                'enabled' => true,
                'percentage' => $request->percentage,
            ],
            'currentFlags' => $currentFlags,
        ]);
        if (!$conflictResult->passed) {
            $errors[] = $conflictResult->error;
        }

        // Check targeting rules
        $targetingRule = new FeatureFlagTargetingRule();
        $targetingResult = $targetingRule->evaluate([
            'flagKey' => $request->flagKey,
            'flagType' => $request->percentage !== null ? 'percentage' : 'user_list',
            'targeting' => [
                'percentage' => $request->percentage,
                'userIds' => $request->userIds,
                'ipRanges' => $request->ipRanges,
                'customRules' => $request->customRules,
            ],
        ]);
        if (!$targetingResult->passed) {
            $errors[] = $targetingResult->error;
        }

        return empty($errors)
            ? \Nexus\SettingsManagement\Contracts\RuleValidationResult::success()
            : \Nexus\SettingsManagement\Contracts\RuleValidationResult::failure(implode('; ', $errors));
    }
}
