<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FeatureFlags;

/**
 * Result DTO for feature flag update.
 */
final class FlagUpdateResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $flagId = null,
        public readonly ?string $error = null,
    ) {}

    public static function success(string $flagId): self
    {
        return new self(success: true, flagId: $flagId);
    }

    public static function failure(string $error): self
    {
        return new self(success: false, error: $error);
    }
}
