<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\Configuration;

/**
 * Request DTO for exporting configuration.
 */
final class ConfigurationExportRequest
{
    public function __construct(
        public readonly string $tenantId,
        public readonly bool $includeSettings = true,
        public readonly bool $includeFeatureFlags = true,
        public readonly bool $includeFiscalPeriods = true,
    ) {}
}
