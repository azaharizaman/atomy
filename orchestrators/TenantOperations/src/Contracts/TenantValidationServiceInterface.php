<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

use Nexus\TenantOperations\DTOs\TenantValidationRequest;
use Nexus\TenantOperations\DTOs\TenantValidationResult;
use Nexus\TenantOperations\DTOs\ModulesValidationRequest;
use Nexus\TenantOperations\DTOs\ConfigurationValidationRequest;

/**
 * Service interface for tenant validation operations.
 */
interface TenantValidationServiceInterface
{
    /**
     * Validate tenant is active.
     */
    public function validateActive(string $tenantId): TenantValidationResult;

    /**
     * Validate tenant has required modules enabled.
     */
    public function validateModules(ModulesValidationRequest $request): TenantValidationResult;

    /**
     * Validate tenant has required configurations.
     */
    public function validateConfiguration(ConfigurationValidationRequest $request): TenantValidationResult;

    /**
     * Check if tenant is active.
     */
    public function isTenantActive(string $tenantId): bool;

    /**
     * Check if tenant has module enabled.
     */
    public function hasModuleEnabled(string $tenantId, string $moduleKey): bool;
}
