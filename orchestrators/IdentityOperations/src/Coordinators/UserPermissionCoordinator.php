<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Coordinators;

use Nexus\IdentityOperations\Contracts\UserPermissionCoordinatorInterface;
use Nexus\IdentityOperations\Contracts\UserPermissionServiceInterface;
use Nexus\IdentityOperations\Contracts\UserContextProviderInterface;
use Nexus\IdentityOperations\DTOs\PermissionAssignRequest;
use Nexus\IdentityOperations\DTOs\PermissionAssignResult;
use Nexus\IdentityOperations\DTOs\PermissionRevokeRequest;
use Nexus\IdentityOperations\DTOs\PermissionRevokeResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for user permission management.
 */
final readonly class UserPermissionCoordinator implements UserPermissionCoordinatorInterface
{
    public function __construct(
        private UserPermissionServiceInterface $permissionService,
        private UserContextProviderInterface $contextDataProvider,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getName(): string
    {
        return 'UserPermissionCoordinator';
    }

    public function hasRequiredData(string $userId): bool
    {
        return $this->contextDataProvider->userExists($userId);
    }

    public function assign(PermissionAssignRequest $request): PermissionAssignResult
    {
        $this->logger->info('Processing permission assignment', [
            'user_id' => $request->userId,
            'permission' => $request->permission,
        ]);

        return $this->permissionService->assign($request);
    }

    public function revoke(PermissionRevokeRequest $request): PermissionRevokeResult
    {
        $this->logger->info('Processing permission revocation', [
            'user_id' => $request->userId,
            'permission' => $request->permission,
        ]);

        return $this->permissionService->revoke($request);
    }

    public function hasPermission(string $userId, string $permission, ?string $tenantId = null): bool
    {
        return $this->permissionService->hasPermission($userId, $permission, $tenantId);
    }

    public function getUserPermissions(string $userId, ?string $tenantId = null): array
    {
        return $this->permissionService->getUserPermissions($userId, $tenantId);
    }

    public function getUserRoles(string $userId, ?string $tenantId = null): array
    {
        return $this->permissionService->getUserRoles($userId, $tenantId);
    }

    public function assignRole(string $userId, string $roleId, string $tenantId, string $assignedBy): bool
    {
        $this->logger->info('Processing role assignment', [
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);

        return $this->permissionService->assignRole($userId, $roleId, $tenantId, $assignedBy);
    }

    public function revokeRole(string $userId, string $roleId, string $tenantId, string $revokedBy): bool
    {
        $this->logger->info('Processing role revocation', [
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);

        return $this->permissionService->revokeRole($userId, $roleId, $tenantId, $revokedBy);
    }
}
