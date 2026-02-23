<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Exceptions;

/**
 * Exception thrown when a setting is not found.
 */
class SettingNotFoundException extends SettingsManagementException
{
    public static function forKey(string $key, ?string $tenantId = null): self
    {
        return new self(
            message: "Setting '{$key}' not found" . ($tenantId ? " for tenant {$tenantId}" : ''),
            context: ['key' => $key, 'tenant_id' => $tenantId]
        );
    }
}
