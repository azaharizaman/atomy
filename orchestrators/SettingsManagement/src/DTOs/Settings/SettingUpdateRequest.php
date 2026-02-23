<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\Settings;

/**
 * Request DTO for updating a single setting.
 */
final class SettingUpdateRequest
{
    public function __construct(
        public readonly string $key,
        public readonly mixed $value,
        public readonly ?string $tenantId = null,
        public readonly ?string $userId = null,
        public readonly string $reason = '',
    ) {}
}
