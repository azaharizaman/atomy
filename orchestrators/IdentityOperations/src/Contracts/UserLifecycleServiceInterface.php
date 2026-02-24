<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

use Nexus\IdentityOperations\DTOs\UserSuspendRequest;
use Nexus\IdentityOperations\DTOs\UserSuspendResult;
use Nexus\IdentityOperations\DTOs\UserActivateRequest;
use Nexus\IdentityOperations\DTOs\UserActivateResult;
use Nexus\IdentityOperations\DTOs\UserDeactivateRequest;
use Nexus\IdentityOperations\DTOs\UserDeactivateResult;

/**
 * Service interface for user lifecycle operations.
 */
interface UserLifecycleServiceInterface
{
    /**
     * Suspend a user.
     */
    public function suspend(UserSuspendRequest $request): UserSuspendResult;

    /**
     * Activate a suspended user.
     */
    public function activate(UserActivateRequest $request): UserActivateResult;

    /**
     * Deactivate a user permanently.
     */
    public function deactivate(UserDeactivateRequest $request): UserDeactivateResult;

    /**
     * Force logout for a user.
     */
    public function forceLogout(string $userId, string $performedBy): bool;

    /**
     * Disable user access.
     */
    public function disableAccess(string $userId): void;

    /**
     * Enable user access.
     */
    public function enableAccess(string $userId): void;
}
