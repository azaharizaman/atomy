<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\Configuration;

/**
 * Result DTO for configuration rollback.
 */
final class ConfigurationRollbackResult
{
    public function __construct(
        public readonly bool $success,
        public readonly int $rolledBackToVersion = 0,
        public readonly ?string $error = null,
    ) {}

    public static function success(int $version): self
    {
        return new self(success: true, rolledBackToVersion: $version);
    }

    public static function failure(string $error): self
    {
        return new self(success: false, error: $error);
    }
}
