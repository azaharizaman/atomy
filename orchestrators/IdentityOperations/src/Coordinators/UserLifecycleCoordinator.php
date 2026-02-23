<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Coordinators;

use Nexus\IdentityOperations\Contracts\UserLifecycleCoordinatorInterface;
use Nexus\IdentityOperations\DTOs\UserSuspendRequest;
use Nexus\IdentityOperations\DTOs\UserSuspendResult;
use Nexus\IdentityOperations\DTOs\UserActivateRequest;
use Nexus\IdentityOperations\DTOs\UserActivateResult;
use Nexus\IdentityOperations\DTOs\UserDeactivateRequest;
use Nexus\IdentityOperations\DTOs\UserDeactivateResult;
use Nexus\IdentityOperations\Services\UserLifecycleService;
use Nexus\IdentityOperations\DataProviders\UserContextDataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for user lifecycle operations.
 * 
 * Manages user state transitions: suspend, activate, deactivate.
 */
final readonly class UserLifecycleCoordinator implements UserLifecycleCoordinatorInterface
{
    public function __construct(
        private UserLifecycleService $lifecycleService,
        private UserContextDataProvider $contextDataProvider,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getName(): string
    {
        return 'UserLifecycleCoordinator';
    }

    public function hasRequiredData(string $userId): bool
    {
        return $this->contextDataProvider->userExists($userId);
    }

    public function suspend(UserSuspendRequest $request): UserSuspendResult
    {
        $this->logger->info('Processing user suspension', [
            'user_id' => $request->userId,
        ]);

        return $this->lifecycleService->suspend($request);
    }

    public function activate(UserActivateRequest $request): UserActivateResult
    {
        $this->logger->info('Processing user activation', [
            'user_id' => $request->userId,
        ]);

        return $this->lifecycleService->activate($request);
    }

    public function deactivate(UserDeactivateRequest $request): UserDeactivateResult
    {
        $this->logger->info('Processing user deactivation', [
            'user_id' => $request->userId,
        ]);

        return $this->lifecycleService->deactivate($request);
    }

    public function forceLogout(string $userId, string $performedBy): bool
    {
        $this->logger->info('Processing force logout', [
            'user_id' => $userId,
            'performed_by' => $performedBy,
        ]);

        return $this->lifecycleService->forceLogout($userId, $performedBy);
    }
}
