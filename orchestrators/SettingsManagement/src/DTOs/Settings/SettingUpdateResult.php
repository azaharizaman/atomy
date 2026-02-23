<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\Settings;

/**
 * Result DTO for setting update operation.
 */
final class SettingUpdateResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $error = null,
        public readonly ?string $settingKey = null,
        public readonly mixed $oldValue = null,
        public readonly mixed $newValue = null,
    ) {}

    public static function success(string $key, mixed $oldValue, mixed $newValue): self
    {
        return new self(
            success: true,
            settingKey: $key,
            oldValue: $oldValue,
            newValue: $newValue,
        );
    }

    public static function failure(string $error): self
    {
        return new self(success: false, error: $error);
    }
}
