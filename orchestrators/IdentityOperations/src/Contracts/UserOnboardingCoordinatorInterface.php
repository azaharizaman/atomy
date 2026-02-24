<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

use Nexus\IdentityOperations\DTOs\UserCreateRequest;
use Nexus\IdentityOperations\DTOs\UserCreateResult;
use Nexus\IdentityOperations\DTOs\UserUpdateRequest;
use Nexus\IdentityOperations\DTOs\UserUpdateResult;

/**
 * Coordinator interface for user onboarding operations.
 */
interface UserOnboardingCoordinatorInterface extends CoordinatorInterface
{
    /**
     * Create a new user and assign to tenant.
     */
    public function createUser(UserCreateRequest $request): UserCreateResult;

    /**
     * Update user information.
     */
    public function updateUser(UserUpdateRequest $request): UserUpdateResult;

    /**
     * Setup initial permissions for a new user.
     */
    public function setupInitialPermissions(string $userId, string $tenantId, array $roles): bool;

    /**
     * Send welcome notification to user.
     */
    public function sendWelcomeNotification(string $userId, ?string $temporaryPassword = null): bool;
}
