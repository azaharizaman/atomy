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
    public function suspend(UserSuspendRequest $request): UserSuspendResult;

    public function activate(UserActivateRequest $request): UserActivateResult;

    public function deactivate(UserDeactivateRequest $request): UserDeactivateResult;

    public function forceLogout(string $userId, string $performedBy, string $tenantId): bool;

    public function disableAccess(string $userId): void;

    public function enableAccess(string $userId): void;
}
