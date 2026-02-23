<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\Settings;

/**
 * Request DTO for bulk updating settings.
 * 
 * @param array<string, mixed> $settings Key-value pairs of settings to update
 */
final class BulkSettingUpdateRequest
{
    /**
     * @param array<string, mixed> $settings Key-value pairs of settings to update
     */
    public function __construct(
        public readonly array $settings,
        public readonly ?string $tenantId = null,
        public readonly ?string $userId = null,
        public readonly string $reason = '',
    ) {}
}
