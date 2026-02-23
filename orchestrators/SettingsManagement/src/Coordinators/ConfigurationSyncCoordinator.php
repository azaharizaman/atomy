<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Coordinators;

use Nexus\SettingsManagement\Contracts\ConfigurationSyncCoordinatorInterface;
use Nexus\SettingsManagement\Contracts\ConfigurationSyncServiceInterface;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationExportRequest;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationExportResult;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationImportRequest;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationImportResult;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationRollbackRequest;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationRollbackResult;
use Nexus\SettingsManagement\Rules\ConfigurationImportRule;

/**
 * Coordinator for managing configuration synchronization (import/export/rollback).
 * Orchestrates validation rules and services.
 */
final class ConfigurationSyncCoordinator implements ConfigurationSyncCoordinatorInterface
{
    public function __construct(
        private readonly ConfigurationSyncServiceInterface $syncService,
    ) {}

    public function getName(): string
    {
        return 'ConfigurationSyncCoordinator';
    }

    public function hasRequiredData(string $tenantId): bool
    {
        // Check if there's any configuration to export
        $history = $this->syncService->getConfigurationHistory($tenantId, 1);
        return !empty($history) || true; // Always allow export
    }

    public function exportConfiguration(ConfigurationExportRequest $request): ConfigurationExportResult
    {
        return $this->syncService->exportConfiguration($request);
    }

    public function importConfiguration(ConfigurationImportRequest $request): ConfigurationImportResult
    {
        // Validate import data first
        $validationResult = $this->validateImport($request);
        
        if (!$validationResult->passed) {
            return ConfigurationImportResult::failure(
                $validationResult->error ?? 'Validation failed',
                $validationResult->details['errors'] ?? []
            );
        }

        // Check if validation only
        if ($request->validateOnly) {
            return ConfigurationImportResult::validationSuccess();
        }

        return $this->syncService->importConfiguration($request);
    }

    public function rollbackConfiguration(ConfigurationRollbackRequest $request): ConfigurationRollbackResult
    {
        // Check if version exists
        $version = $this->syncService->getConfigurationVersion($request->tenantId, $request->targetVersion);
        
        if (!$version && $request->targetVersion > 0) {
            return ConfigurationRollbackResult::failure("Version {$request->targetVersion} not found");
        }

        return $this->syncService->rollbackConfiguration($request);
    }

    public function getConfigurationHistory(string $tenantId, int $limit = 10): array
    {
        return $this->syncService->getConfigurationHistory($tenantId, $limit);
    }

    public function getConfigurationVersion(string $tenantId, int $version): ?array
    {
        return $this->syncService->getConfigurationVersion($tenantId, $version);
    }

    private function validateImport(ConfigurationImportRequest $request): \Nexus\SettingsManagement\Contracts\RuleValidationResult
    {
        $importRule = new ConfigurationImportRule();
        return $importRule->evaluate(['jsonData' => $request->jsonData]);
    }
}
