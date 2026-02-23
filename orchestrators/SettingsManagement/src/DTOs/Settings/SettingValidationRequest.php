<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\Settings;

/**
 * Request DTO for validating a setting change.
 */
final class SettingValidationRequest
{
    public function __construct(
        public readonly string $key,
        public readonly mixed $value,
        public readonly ?string $tenantId = null,
        public readonly ?string $userId = null,
    ) {}
}
