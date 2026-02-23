<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Exceptions;

/**
 * Exception thrown when setting validation fails.
 */
class SettingValidationException extends SettingsManagementException
{
    public static function invalidValue(string $key, mixed $value, array $errors = []): self
    {
        return new self(
            message: "Setting '{$key}' validation failed: " . implode('; ', $errors),
            context: ['key' => $key, 'value' => $value, 'errors' => $errors]
        );
    }
}
