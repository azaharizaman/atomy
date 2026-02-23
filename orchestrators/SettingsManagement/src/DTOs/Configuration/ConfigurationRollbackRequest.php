<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\Configuration;

/**
 * Request DTO for rolling back configuration.
 */
final class ConfigurationRollbackRequest
{
    public function __construct(
        public readonly string $tenantId,
        public readonly int $targetVersion,
        public readonly bool $dryRun = false,
    ) {}
}
