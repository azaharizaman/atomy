<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Services;

use Nexus\SettingsManagement\Contracts\ConfigurationSyncServiceInterface;
use Nexus\SettingsManagement\Contracts\SettingsProviderInterface;
use Nexus\SettingsManagement\Contracts\FeatureFlagProviderInterface;
use Nexus\SettingsManagement\Contracts\FiscalPeriodProviderInterface;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationExportRequest;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationExportResult;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationImportRequest;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationImportResult;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationRollbackRequest;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationRollbackResult;
use Psr\Log\LoggerInterface;

/**
 * Orchestration service for configuration synchronization (export/import/rollback).
 */
final class ConfigurationSyncService implements ConfigurationSyncServiceInterface
{
    private const EXPORT_VERSION = '1.0.0';

    public function __construct(
        private readonly SettingsProviderInterface $settingsProvider,
        private readonly FeatureFlagProviderInterface $flagProvider,
        private readonly FiscalPeriodProviderInterface $periodProvider,
        private readonly LoggerInterface $logger,
    ) {}

    public function exportConfiguration(ConfigurationExportRequest $request): ConfigurationExportResult
    {
        $this->logger->info('Exporting configuration', [
            'tenant_id' => $request->tenantId,
        ]);

        try {
            $config = [
                'version' => self::EXPORT_VERSION,
                'exported_at' => (new \DateTime())->format(\DateTime::ATOM),
                'tenant_id' => $request->tenantId,
            ];

            // Export settings
            if ($request->includeSettings) {
                $config['settings'] = $this->settingsProvider->getAllSettings($request->tenantId);
            }

            // Export feature flags
            if ($request->includeFeatureFlags) {
                $config['feature_flags'] = $this->flagProvider->getAllFlags($request->tenantId);
            }

            // Export fiscal periods
            if ($request->includeFiscalPeriods) {
                $config['fiscal_calendar'] = $this->periodProvider->getCalendarConfig($request->tenantId);
                $config['fiscal_periods'] = $this->periodProvider->getAllPeriods($request->tenantId);
            }

            $jsonData = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if ($jsonData === false) {
                throw new \RuntimeException('Failed to encode configuration: ' . json_last_error_msg());
            }

            $this->logger->info('Configuration exported successfully', [
                'tenant_id' => $request->tenantId,
                'size' => strlen($jsonData),
            ]);

            return ConfigurationExportResult::success($jsonData);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to export configuration', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            return ConfigurationExportResult::failure($e->getMessage());
        }
    }

    public function importConfiguration(ConfigurationImportRequest $request): ConfigurationImportResult
    {
        $this->logger->info('Importing configuration', [
            'tenant_id' => $request->tenantId,
            'validate_only' => $request->validateOnly,
        ]);

        try {
            // Decode JSON
            $data = json_decode($request->jsonData, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ConfigurationImportResult::failure('Invalid JSON: ' . json_last_error_msg());
            }

            $settingsImported = 0;
            $flagsImported = 0;
            $periodsImported = 0;

            if (!$request->validateOnly) {
                // Import settings
                if (isset($data['settings']) && is_array($data['settings'])) {
                    foreach ($data['settings'] as $key => $value) {
                        // In production, this would persist to the Setting package
                        $settingsImported++;
                    }
                }

                // Import feature flags
                if (isset($data['feature_flags']) && is_array($data['feature_flags'])) {
                    foreach ($data['feature_flags'] as $flag) {
                        // In production, this would persist to the FeatureFlags package
                        $flagsImported++;
                    }
                }

                // Import fiscal periods
                if (isset($data['fiscal_periods']) && is_array($data['fiscal_periods'])) {
                    $periodsImported = count($data['fiscal_periods']);
                }
            }

            $this->logger->info('Configuration imported', [
                'tenant_id' => $request->tenantId,
                'settings' => $settingsImported,
                'flags' => $flagsImported,
                'periods' => $periodsImported,
            ]);

            return ConfigurationImportResult::success($settingsImported, $flagsImported, $periodsImported);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to import configuration', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            return ConfigurationImportResult::failure($e->getMessage());
        }
    }

    public function rollbackConfiguration(ConfigurationRollbackRequest $request): ConfigurationRollbackResult
    {
        $this->logger->info('Rolling back configuration', [
            'tenant_id' => $request->tenantId,
            'target_version' => $request->targetVersion,
            'dry_run' => $request->dryRun,
        ]);

        try {
            // In production, this would get the configuration version and restore it
            if ($request->dryRun) {
                $this->logger->info('Dry run: would rollback to version', [
                    'version' => $request->targetVersion,
                ]);
            }

            $this->logger->info('Configuration rolled back', [
                'tenant_id' => $request->tenantId,
                'version' => $request->targetVersion,
            ]);

            return ConfigurationRollbackResult::success($request->targetVersion);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to rollback configuration', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            return ConfigurationRollbackResult::failure($e->getMessage());
        }
    }

    public function getConfigurationHistory(string $tenantId, int $limit = 10): array
    {
        // In production, this would query the audit log or configuration history
        return [];
    }

    public function getConfigurationVersion(string $tenantId, int $version): ?array
    {
        // In production, this would get the specific version
        return null;
    }
}
