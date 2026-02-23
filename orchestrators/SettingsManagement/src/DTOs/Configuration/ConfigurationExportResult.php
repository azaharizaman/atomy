<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\Configuration;

/**
 * Result DTO for configuration export.
 */
final class ConfigurationExportResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $jsonData = null,
        public readonly ?string $error = null,
    ) {}

    public static function success(string $jsonData): self
    {
        return new self(success: true, jsonData: $jsonData);
    }

    public static function failure(string $error): self
    {
        return new self(success: false, error: $error);
    }
}
