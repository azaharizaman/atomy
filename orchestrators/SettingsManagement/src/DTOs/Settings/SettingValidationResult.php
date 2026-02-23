<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\Settings;

/**
 * Result DTO for setting validation.
 * 
 * @param array<string> $errors Validation error messages
 */
final class SettingValidationResult
{
    /**
     * @param array<string> $errors
     */
    public function __construct(
        public readonly bool $valid,
        public readonly array $errors = [],
    ) {}

    public static function valid(): self
    {
        return new self(valid: true);
    }

    public static function invalid(array $errors): self
    {
        return new self(valid: false, errors: $errors);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
