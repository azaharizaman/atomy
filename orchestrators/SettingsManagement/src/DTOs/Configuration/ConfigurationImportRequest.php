<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\Configuration;

/**
 * Request DTO for importing configuration.
 */
final class ConfigurationImportRequest
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $jsonData,
        public readonly bool $validateOnly = false,
        public readonly bool $overwriteExisting = true,
    ) {}
}
