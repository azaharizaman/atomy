<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

use Nexus\TenantOperations\DTOs\TenantValidationRequest;
use Nexus\TenantOperations\DTOs\TenantValidationResult;
use Nexus\TenantOperations\DTOs\ModulesValidationRequest;
use Nexus\TenantOperations\DTOs\ConfigurationValidationRequest;

/**
 * Coordinator interface for tenant validation operations.
 * Used by other orchestrators to validate tenant state.
 */
interface TenantValidationCoordinatorInterface extends TenantCoordinatorInterface
{
    /**
     * Validate tenant is active and has valid subscription.
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
}
