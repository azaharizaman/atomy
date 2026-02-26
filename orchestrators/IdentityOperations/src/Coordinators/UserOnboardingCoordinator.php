<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Coordinators;

use Nexus\IdentityOperations\Contracts\UserOnboardingCoordinatorInterface;
use Nexus\IdentityOperations\Contracts\UserOnboardingServiceInterface;
use Nexus\IdentityOperations\Contracts\UserContextProviderInterface;
use Nexus\IdentityOperations\DTOs\UserCreateRequest;
use Nexus\IdentityOperations\DTOs\UserCreateResult;
use Nexus\IdentityOperations\DTOs\UserUpdateRequest;
use Nexus\IdentityOperations\DTOs\UserUpdateResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for user onboarding operations.
 */
final readonly class UserOnboardingCoordinator implements UserOnboardingCoordinatorInterface
{
    public function __construct(
        private UserOnboardingServiceInterface $onboardingService,
        private UserContextProviderInterface $contextDataProvider,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getName(): string
    {
        return 'UserOnboardingCoordinator';
    }

    public function hasRequiredData(string $userId): bool
    {
        return $this->contextDataProvider->userExists($userId);
    }

    public function createUser(UserCreateRequest $request): UserCreateResult
    {
        $this->logger->info('Processing user creation', [
            'email' => $request->email,
        ]);

        return $this->onboardingService->createUser($request);
    }

    public function updateUser(UserUpdateRequest $request): UserUpdateResult
    {
        $this->logger->info('Processing user update', [
            'user_id' => $request->userId,
        ]);

        return $this->onboardingService->updateUser($request);
    }

    public function setupInitialPermissions(string $userId, string $tenantId, array $roles): bool
    {
        $this->logger->info('Setting up initial permissions', [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'roles' => $roles,
        ]);

        return $this->onboardingService->assignToTenant($userId, $tenantId, $roles);
    }

    public function sendWelcomeNotification(string $userId, ?string $temporaryPassword = null): bool
    {
        $this->logger->info('Sending welcome notification', [
            'user_id' => $userId,
        ]);

        return $this->onboardingService->sendWelcomeNotification($userId, $temporaryPassword);
    }
}
