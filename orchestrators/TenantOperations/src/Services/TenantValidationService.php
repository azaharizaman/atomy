<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Services;

use Nexus\TenantOperations\Contracts\TenantValidationServiceInterface;
use Nexus\TenantOperations\DTOs\TenantValidationResult;
use Nexus\TenantOperations\DTOs\ModulesValidationRequest;
use Nexus\TenantOperations\DTOs\ConfigurationValidationRequest;
use Nexus\TenantOperations\Rules\TenantStatusCheckerInterface;
use Nexus\TenantOperations\Rules\ModuleCheckerInterface;
use Nexus\TenantOperations\Rules\ConfigurationCheckerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for tenant validation operations.
 * 
 * Validates tenant state for use by other orchestrators.
 */
final readonly class TenantValidationService implements TenantValidationServiceInterface
{
    public function __construct(
        private TenantStatusCheckerInterface $statusChecker,
        private ModuleCheckerInterface $moduleChecker,
        private ConfigurationCheckerInterface $configChecker,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function validateActive(string $tenantId): TenantValidationResult
    {
        $this->logger->debug('Validating tenant active status', [
            'tenant_id' => $tenantId,
        ]);

        $isActive = $this->isTenantActive($tenantId);

        if ($isActive) {
            return TenantValidationResult::valid($tenantId);
        }

        return TenantValidationResult::invalid(
            tenantId: $tenantId,
            errors: [
                [
                    'rule' => 'tenant_active',
                    'message' => 'Tenant is not active',
                    'severity' => 'error',
                ],
            ],
            message: 'Tenant validation failed: tenant is not active'
        );
    }

    public function validateModules(ModulesValidationRequest $request): TenantValidationResult
    {
        $this->logger->debug('Validating tenant modules', [
            'tenant_id' => $request->tenantId,
            'required_modules' => $request->requiredModules,
        ]);

        $errors = [];
        $tenantId = $request->tenantId;

        foreach ($request->requiredModules as $module) {
            $isEnabled = $this->hasModuleEnabled($tenantId, $module);

            if (!$isEnabled) {
                $errors[] = [
                    'rule' => 'tenant_modules_enabled',
                    'message' => "Module '{$module}' is not enabled",
                    'severity' => 'error',
                ];
            }
        }

        if (empty($errors)) {
            return TenantValidationResult::valid($tenantId);
        }

        return TenantValidationResult::invalid(
            tenantId: $tenantId,
            errors: $errors,
            message: 'Module validation failed'
        );
    }

    public function validateConfiguration(ConfigurationValidationRequest $request): TenantValidationResult
    {
        $this->logger->debug('Validating tenant configuration', [
            'tenant_id' => $request->tenantId,
            'required_configs' => $request->requiredConfigs,
        ]);

        $errors = [];
        $tenantId = $request->tenantId;

        foreach ($request->requiredConfigs as $config) {
            $exists = $this->configChecker->configurationExists($tenantId, $config);

            if (!$exists) {
                $errors[] = [
                    'rule' => 'tenant_configuration_exists',
                    'message' => "Configuration '{$config}' is not set",
                    'severity' => 'error',
                ];
            }
        }

        if (empty($errors)) {
            return TenantValidationResult::valid($tenantId);
        }

        return TenantValidationResult::invalid(
            tenantId: $tenantId,
            errors: $errors,
            message: 'Configuration validation failed'
        );
    }

    public function isTenantActive(string $tenantId): bool
    {
        return $this->statusChecker->isActive($tenantId);
    }

    public function hasModuleEnabled(string $tenantId, string $moduleKey): bool
    {
        return $this->moduleChecker->isModuleEnabled($tenantId, $moduleKey);
    }
}
