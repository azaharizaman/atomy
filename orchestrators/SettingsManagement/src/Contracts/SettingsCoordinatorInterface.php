<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Contracts;

use Nexus\SettingsManagement\DTOs\Settings\SettingUpdateRequest;
use Nexus\SettingsManagement\DTOs\Settings\SettingUpdateResult;
use Nexus\SettingsManagement\DTOs\Settings\BulkSettingUpdateRequest;
use Nexus\SettingsManagement\DTOs\Settings\BulkSettingUpdateResult;
use Nexus\SettingsManagement\DTOs\Settings\SettingValidationRequest;
use Nexus\SettingsManagement\DTOs\Settings\SettingValidationResult;

/**
 * Coordinator interface for managing application settings.
 */
interface SettingsCoordinatorInterface extends SettingsCoordinatorInterfaceBase
{
    /**
     * Update a single setting.
     */
    public function updateSetting(SettingUpdateRequest $request): SettingUpdateResult;

    /**
     * Update multiple settings in a transaction.
     */
    public function bulkUpdateSettings(BulkSettingUpdateRequest $request): BulkSettingUpdateResult;

    /**
     * Validate setting change before applying.
     */
    public function validateSettingChange(SettingValidationRequest $request): SettingValidationResult;

    /**
     * Resolve setting value from hierarchy (application, tenant, user).
     */
    public function resolveSettingValue(string $key, ?string $tenantId, ?string $userId): mixed;

    /**
     * Get all settings for a tenant.
     */
    public function getAllSettings(string $tenantId): array;
}
