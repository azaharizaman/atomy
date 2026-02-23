<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Coordinators;

use Nexus\SettingsManagement\Contracts\SettingsCoordinatorInterface;
use Nexus\SettingsManagement\Contracts\SettingsProviderInterface;
use Nexus\SettingsManagement\Contracts\SettingsUpdateServiceInterface;
use Nexus\SettingsManagement\DTOs\Settings\BulkSettingUpdateRequest;
use Nexus\SettingsManagement\DTOs\Settings\BulkSettingUpdateResult;
use Nexus\SettingsManagement\DTOs\Settings\SettingUpdateRequest;
use Nexus\SettingsManagement\DTOs\Settings\SettingUpdateResult;
use Nexus\SettingsManagement\DTOs\Settings\SettingValidationRequest;
use Nexus\SettingsManagement\DTOs\Settings\SettingValidationResult;
use Nexus\SettingsManagement\Rules\SettingValueValidRule;
use Nexus\SettingsManagement\Rules\SettingDependencyRule;
use Nexus\SettingsManagement\Rules\SettingConflictRule;

/**
 * Coordinator for managing application settings.
 * Orchestrates validation rules, data providers, and services.
 */
final class SettingsCoordinator implements SettingsCoordinatorInterface
{
    public function __construct(
        private readonly SettingsProviderInterface $settingsProvider,
        private readonly SettingsUpdateServiceInterface $settingsService,
    ) {}

    public function getName(): string
    {
        return 'SettingsCoordinator';
    }

    public function hasRequiredData(string $tenantId): bool
    {
        $settings = $this->settingsProvider->getAllSettings($tenantId);
        return !empty($settings);
    }

    public function updateSetting(SettingUpdateRequest $request): SettingUpdateResult
    {
        // Validate before updating
        $validationResult = $this->validateSettingChange(new SettingValidationRequest(
            key: $request->key,
            value: $request->value,
            tenantId: $request->tenantId,
            userId: $request->userId,
        ));

        if (!$validationResult->valid) {
            return SettingUpdateResult::failure(implode('; ', $validationResult->errors));
        }

        return $this->settingsService->updateSetting($request);
    }

    public function bulkUpdateSettings(BulkSettingUpdateRequest $request): BulkSettingUpdateResult
    {
        // Validate all settings before bulk update
        foreach ($request->settings as $key => $value) {
            $validationResult = $this->validateSettingChange(new SettingValidationRequest(
                key: $key,
                value: $value,
                tenantId: $request->tenantId,
                userId: $request->userId,
            ));

            if (!$validationResult->valid) {
                return BulkSettingUpdateResult::failure(
                    "Validation failed for setting '{$key}': " . implode('; ', $validationResult->errors),
                    [$key]
                );
            }
        }

        return $this->settingsService->bulkUpdateSettings($request);
    }

    public function validateSettingChange(SettingValidationRequest $request): SettingValidationResult
    {
        $errors = [];

        // Run validation rules
        $valueValidRule = new SettingValueValidRule();
        $valueResult = $valueValidRule->evaluate($request);
        if (!$valueResult->passed) {
            $errors[] = $valueResult->error;
        }

        // Get current settings for dependency/conflict checks
        $currentSettings = [];
        if ($request->tenantId) {
            $settings = $this->settingsProvider->getAllSettings($request->tenantId);
            foreach ($settings as $setting) {
                $currentSettings[$setting['key'] ?? ''] = $setting['value'];
            }
        }

        // Check dependencies
        $dependencyRule = new SettingDependencyRule();
        $depResult = $dependencyRule->evaluate([
            'key' => $request->key,
            'value' => $request->value,
            'tenantId' => $request->tenantId,
            'currentSettings' => $currentSettings,
        ]);
        if (!$depResult->passed) {
            $errors[] = $depResult->error;
        }

        // Check conflicts
        $conflictRule = new SettingConflictRule();
        $conflictResult = $conflictRule->evaluate([
            'key' => $request->key,
            'value' => $request->value,
            'currentSettings' => $currentSettings,
        ]);
        if (!$conflictResult->passed) {
            $errors[] = $conflictResult->error;
        }

        return empty($errors)
            ? SettingValidationResult::valid()
            : SettingValidationResult::invalid($errors);
    }

    public function resolveSettingValue(string $key, ?string $tenantId, ?string $userId): mixed
    {
        return $this->settingsService->resolveSettingValue($key, $tenantId, $userId);
    }

    public function getAllSettings(string $tenantId): array
    {
        return $this->settingsProvider->getAllSettings($tenantId);
    }
}
