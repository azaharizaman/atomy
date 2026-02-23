<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Contracts;

use Nexus\SettingsManagement\DTOs\Settings\SettingUpdateRequest;
use Nexus\SettingsManagement\DTOs\Settings\SettingUpdateResult;
use Nexus\SettingsManagement\DTOs\Settings\BulkSettingUpdateRequest;
use Nexus\SettingsManagement\DTOs\Settings\BulkSettingUpdateResult;

/**
 * Interface for settings update orchestration service.
 */
interface SettingsUpdateServiceInterface
{
    /**
     * Update a single setting with validation and audit logging.
     */
    public function updateSetting(SettingUpdateRequest $request): SettingUpdateResult;

    /**
     * Update multiple settings in a transaction.
     */
    public function bulkUpdateSettings(BulkSettingUpdateRequest $request): BulkSettingUpdateResult;

    /**
     * Resolve setting value from hierarchy.
     */
    public function resolveSettingValue(string $key, ?string $tenantId, ?string $userId): mixed;
}
