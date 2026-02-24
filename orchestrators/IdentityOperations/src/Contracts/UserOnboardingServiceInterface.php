<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

use Nexus\IdentityOperations\DTOs\UserCreateRequest;
use Nexus\IdentityOperations\DTOs\UserCreateResult;
use Nexus\IdentityOperations\DTOs\UserUpdateRequest;
use Nexus\IdentityOperations\DTOs\UserUpdateResult;

/**
 * Service interface for user onboarding operations.
 */
interface UserOnboardingServiceInterface
{
    /**
     * Create a new user.
     */
    public function createUser(UserCreateRequest $request): UserCreateResult;

    /**
     * Update user information.
     */
    public function updateUser(UserUpdateRequest $request): UserUpdateResult;

    /**
     * Assign user to a tenant.
     */
    public function assignToTenant(string $userId, string $tenantId, array $roles): bool;

    /**
     * Send welcome notification.
     */
    public function sendWelcomeNotification(string $userId, ?string $temporaryPassword = null): bool;
}
