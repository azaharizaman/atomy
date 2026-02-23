<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FeatureFlags;

/**
 * Result DTO for disabling a feature flag.
 */
final class FlagDisableResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $flagId = null,
        public readonly ?string $flagKey = null,
        public readonly ?string $error = null,
    ) {}

    public static function success(string $flagId, string $flagKey): self
    {
        return new self(success: true, flagId: $flagId, flagKey: $flagKey);
    }

    public static function failure(string $error): self
    {
        return new self(success: false, error: $error);
    }
}
