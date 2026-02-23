<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FeatureFlags;

/**
 * Request DTO for creating a feature flag.
 */
final class FlagCreateRequest
{
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $description,
        public readonly FlagType $type,
        public readonly mixed $defaultValue,
        public readonly ?string $owner = null,
        public readonly ?string $tenantId = null,
    ) {}
}
