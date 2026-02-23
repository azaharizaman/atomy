<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Coordinators;

use Nexus\TenantOperations\Contracts\TenantValidationCoordinatorInterface;
use Nexus\TenantOperations\DTOs\TenantValidationResult;
use Nexus\TenantOperations\DTOs\ModulesValidationRequest;
use Nexus\TenantOperations\DTOs\ConfigurationValidationRequest;
use Nexus\TenantOperations\Services\TenantValidationService;
use Nexus\TenantOperations\DataProviders\TenantContextDataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for tenant validation operations.
 * 
 * Validates tenant state for use by other orchestrators.
 */
final readonly class TenantValidationCoordinator implements TenantValidationCoordinatorInterface
{
    public function __construct(
        private TenantValidationService $validationService,
        private TenantContextDataProvider $contextDataProvider,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getName(): string
    {
        return 'TenantValidationCoordinator';
    }

    public function hasRequiredData(string $tenantId): bool
    {
        return $this->contextDataProvider->tenantExists($tenantId);
    }

    public function validateActive(string $tenantId): TenantValidationResult
    {
        $this->logger->debug('Validating tenant active', [
            'tenant_id' => $tenantId,
        ]);

        return $this->validationService->validateActive($tenantId);
    }

    public function validateModules(ModulesValidationRequest $request): TenantValidationResult
    {
        $this->logger->debug('Validating tenant modules', [
            'tenant_id' => $request->tenantId,
            'required_modules' => $request->requiredModules,
        ]);

        return $this->validationService->validateModules($request);
    }

    public function validateConfiguration(ConfigurationValidationRequest $request): TenantValidationResult
    {
        $this->logger->debug('Validating tenant configuration', [
            'tenant_id' => $request->tenantId,
            'required_configs' => $request->requiredConfigs,
        ]);

        return $this->validationService->validateConfiguration($request);
    }
}
