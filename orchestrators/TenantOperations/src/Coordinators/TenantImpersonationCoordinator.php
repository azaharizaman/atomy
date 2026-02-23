<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Coordinators;

use Nexus\TenantOperations\Contracts\TenantImpersonationCoordinatorInterface;
use Nexus\TenantOperations\DTOs\ImpersonationStartRequest;
use Nexus\TenantOperations\DTOs\ImpersonationStartResult;
use Nexus\TenantOperations\DTOs\ImpersonationEndRequest;
use Nexus\TenantOperations\DTOs\ImpersonationEndResult;
use Nexus\TenantOperations\Services\TenantImpersonationService;
use Nexus\TenantOperations\Rules\ImpersonationAllowedRule;
use Nexus\TenantOperations\DataProviders\TenantContextDataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for tenant impersonation operations.
 * 
 * Manages admin impersonation sessions.
 */
final readonly class TenantImpersonationCoordinator implements TenantImpersonationCoordinatorInterface
{
    public function __construct(
        private TenantImpersonationService $impersonationService,
        private TenantContextDataProvider $contextDataProvider,
        private ImpersonationAllowedRule $impersonationAllowedRule,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getName(): string
    {
        return 'TenantImpersonationCoordinator';
    }

    public function hasRequiredData(string $tenantId): bool
    {
        return $this->contextDataProvider->tenantExists($tenantId);
    }

    public function startImpersonation(ImpersonationStartRequest $request): ImpersonationStartResult
    {
        $this->logger->info('Processing impersonation start', [
            'admin_user_id' => $request->adminUserId,
            'target_tenant_id' => $request->targetTenantId,
        ]);

        // Validate permission
        $permissionResult = $this->impersonationAllowedRule->evaluate($request);
        
        if (!$permissionResult->passed) {
            $this->logger->warning('Impersonation permission denied', [
                'admin_user_id' => $request->adminUserId,
                'errors' => $permissionResult->errors,
            ]);

            return ImpersonationStartResult::failure(
                message: 'Permission denied: ' . ($permissionResult->errors[0]['message'] ?? 'Unknown error')
            );
        }

        // Delegate to service
        return $this->impersonationService->startImpersonation($request);
    }

    public function endImpersonation(ImpersonationEndRequest $request): ImpersonationEndResult
    {
        $this->logger->info('Processing impersonation end', [
            'admin_user_id' => $request->adminUserId,
        ]);

        return $this->impersonationService->endImpersonation($request);
    }
}
