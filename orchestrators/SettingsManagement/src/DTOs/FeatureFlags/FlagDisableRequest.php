<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FeatureFlags;

/**
 * Request DTO for disabling a feature flag.
 */
final class FlagDisableRequest
{
    public function __construct(
        public readonly string $flagId,
        public readonly string $flagKey,
        public readonly bool $gracefulDegradation = true,
        public readonly ?string $reason = null,
    ) {}
}
