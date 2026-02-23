<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Coordinators;

use Nexus\TenantOperations\Contracts\TenantLifecycleCoordinatorInterface;
use Nexus\TenantOperations\DTOs\TenantSuspendRequest;
use Nexus\TenantOperations\DTOs\TenantSuspendResult;
use Nexus\TenantOperations\DTOs\TenantActivateRequest;
use Nexus\TenantOperations\DTOs\TenantActivateResult;
use Nexus\TenantOperations\DTOs\TenantArchiveRequest;
use Nexus\TenantOperations\DTOs\TenantArchiveResult;
use Nexus\TenantOperations\DTOs\TenantDeleteRequest;
use Nexus\TenantOperations\DTOs\TenantDeleteResult;
use Nexus\TenantOperations\Services\TenantLifecycleService;
use Nexus\TenantOperations\DataProviders\TenantContextDataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for tenant lifecycle operations.
 * 
 * Manages tenant state transitions: suspend, activate, archive, delete.
 */
final readonly class TenantLifecycleCoordinator implements TenantLifecycleCoordinatorInterface
{
    public function __construct(
        private TenantLifecycleService $lifecycleService,
        private TenantContextDataProvider $contextDataProvider,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getName(): string
    {
        return 'TenantLifecycleCoordinator';
    }

    public function hasRequiredData(string $tenantId): bool
    {
        return $this->contextDataProvider->tenantExists($tenantId);
    }

    public function suspend(TenantSuspendRequest $request): TenantSuspendResult
    {
        $this->logger->info('Processing tenant suspension', [
            'tenant_id' => $request->tenantId,
        ]);

        return $this->lifecycleService->suspend($request);
    }

    public function activate(TenantActivateRequest $request): TenantActivateResult
    {
        $this->logger->info('Processing tenant activation', [
            'tenant_id' => $request->tenantId,
        ]);

        return $this->lifecycleService->activate($request);
    }

    public function archive(TenantArchiveRequest $request): TenantArchiveResult
    {
        $this->logger->info('Processing tenant archiving', [
            'tenant_id' => $request->tenantId,
        ]);

        return $this->lifecycleService->archive($request);
    }

    public function delete(TenantDeleteRequest $request): TenantDeleteResult
    {
        $this->logger->info('Processing tenant deletion', [
            'tenant_id' => $request->tenantId,
        ]);

        return $this->lifecycleService->delete($request);
    }
}
