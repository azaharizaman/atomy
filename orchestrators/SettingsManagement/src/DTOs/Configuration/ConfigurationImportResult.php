<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\Configuration;

/**
 * Result DTO for configuration import.
 */
final class ConfigurationImportResult
{
    public function __construct(
        public readonly bool $success,
        public readonly bool $validated = false,
        public readonly int $settingsImported = 0,
        public readonly int $flagsImported = 0,
        public readonly int $periodsImported = 0,
        public readonly array $errors = [],
        public readonly ?string $error = null,
    ) {}

    public static function success(int $settings, int $flags, int $periods): self
    {
        return new self(
            success: true,
            validated: true,
            settingsImported: $settings,
            flagsImported: $flags,
            periodsImported: $periods,
        );
    }

    public static function validationSuccess(): self
    {
        return new self(success: true, validated: true);
    }

    public static function failure(string $error, array $errors = []): self
    {
        return new self(success: false, error: $error, errors: $errors);
    }
}
