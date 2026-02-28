<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Services;

use Nexus\SettingsManagement\Contracts\SettingsProviderInterface;
use Nexus\SettingsManagement\Contracts\SettingsPersistProviderInterface;
use Nexus\SettingsManagement\Contracts\SettingsUpdateServiceInterface;
use Nexus\SettingsManagement\DTOs\Settings\BulkSettingUpdateRequest;
use Nexus\SettingsManagement\DTOs\Settings\BulkSettingUpdateResult;
use Nexus\SettingsManagement\DTOs\Settings\SettingUpdateRequest;
use Nexus\SettingsManagement\DTOs\Settings\SettingUpdateResult;
use Psr\Log\LoggerInterface;

/**
 * Orchestration service for settings management.
 * Handles complex settings updates with validation, audit logging, and cache invalidation.
 */
final readonly class SettingsUpdateService implements SettingsUpdateServiceInterface
{
    public function __construct(
        private SettingsProviderInterface $settingsProvider,
        private SettingsPersistProviderInterface $persistProvider,
        private LoggerInterface $logger,
    ) {}

    public function updateSetting(SettingUpdateRequest $request): SettingUpdateResult
    {
        $this->logger->info('Updating setting', [
            'key' => $request->key,
            'tenant_id' => $request->tenantId,
            'user_id' => $request->userId,
        ]);

        $oldValue = null;
        if ($request->tenantId) {
            $existing = $this->settingsProvider->getSetting($request->key, $request->tenantId);
            $oldValue = $existing['value'] ?? null;
        }

        try {
            $this->persistProvider->update($request->key, $request->value, $request->tenantId, $request->userId);

            $this->logger->info('Setting updated successfully', [
                'key' => $request->key,
                'old_value' => $oldValue,
                'new_value' => $request->value,
            ]);

            return SettingUpdateResult::success(
                $request->key,
                $oldValue,
                $request->value
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to update setting', [
                'key' => $request->key,
                'error' => $e->getMessage(),
            ]);

            return SettingUpdateResult::failure($e->getMessage());
        }
    }

    public function bulkUpdateSettings(BulkSettingUpdateRequest $request): BulkSettingUpdateResult
    {
        $this->logger->info('Bulk updating settings', [
            'count' => count($request->settings),
            'tenant_id' => $request->tenantId,
        ]);

        try {
            // Assume persistProvider handles the transaction for atomicity
            $this->persistProvider->bulkUpdate($request->settings, $request->tenantId, $request->userId);
            
            $results = [];
            foreach ($request->settings as $key => $value) {
                $results[] = SettingUpdateResult::success($key, null, $value);
            }

            return BulkSettingUpdateResult::success($results);
        } catch (\Throwable $e) {
            $this->logger->error('Failed bulk update', [
                'error' => $e->getMessage(),
            ]);

            return BulkSettingUpdateResult::failure($e->getMessage(), array_keys($request->settings));
        }
    }

    public function resolveSettingValue(string $key, ?string $tenantId, ?string $userId): mixed
    {
        return $this->settingsProvider->resolveSettingValue($key, $tenantId, $userId);
    }
}
