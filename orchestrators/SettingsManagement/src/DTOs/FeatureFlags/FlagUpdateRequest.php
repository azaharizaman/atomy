<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FeatureFlags;

/**
 * Request DTO for updating a feature flag.
 */
final class FlagUpdateRequest
{
    public function __construct(
        public readonly string $flagId,
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly mixed $defaultValue = null,
        public readonly ?string $owner = null,
    ) {}
}
