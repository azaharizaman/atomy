<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Contracts;

use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationExportRequest;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationExportResult;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationImportRequest;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationImportResult;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationRollbackRequest;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationRollbackResult;

/**
 * Interface for configuration synchronization service.
 */
interface ConfigurationSyncServiceInterface
{
    /**
     * Export configuration to JSON format.
     */
    public function exportConfiguration(ConfigurationExportRequest $request): ConfigurationExportResult;

    /**
     * Import configuration from JSON format.
     */
    public function importConfiguration(ConfigurationImportRequest $request): ConfigurationImportResult;

    /**
     * Rollback configuration to a previous version.
     */
    public function rollbackConfiguration(ConfigurationRollbackRequest $request): ConfigurationRollbackResult;

    /**
     * Get configuration history for a tenant.
     */
    public function getConfigurationHistory(string $tenantId, int $limit = 10): array;

    /**
     * Get specific configuration version.
     */
    public function getConfigurationVersion(string $tenantId, int $version): ?array;
}
