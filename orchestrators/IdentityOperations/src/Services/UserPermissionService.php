<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

use Nexus\IdentityOperations\Contracts\UserPermissionServiceInterface;
use Nexus\IdentityOperations\DTOs\PermissionAssignRequest;
use Nexus\IdentityOperations\DTOs\PermissionAssignResult;
use Nexus\IdentityOperations\DTOs\PermissionRevokeRequest;
use Nexus\IdentityOperations\DTOs\PermissionRevokeResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for user permission operations.
 */
final readonly class UserPermissionService implements UserPermissionServiceInterface
{
    public function __construct(
        private PermissionAssignerInterface $permissionAssigner,
        private PermissionRevokerInterface $permissionRevoker,
        private PermissionCheckerInterface $permissionChecker,
        private RoleAssignerInterface $roleAssigner,
        private RoleRevokerInterface $roleRevoker,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function assign(PermissionAssignRequest $request): PermissionAssignResult
    {
        $this->logger->info('Assigning permission', [
            'user_id' => $request->userId,
            'permission' => $request->permission,
            'tenant_id' => $request->tenantId,
        ]);

        try {
            $permissionId = $this->permissionAssigner->assignPermission(
                userId: $request->userId,
                permission: $request->permission,
                tenantId: $request->tenantId,
                expiresAt: $request->expiresAt,
            );

            $this->auditLogger->log(
                'permission.assigned',
                $request->userId,
                [
                    'permission' => $request->permission,
                    'tenant_id' => $request->tenantId,
                    'assigned_by' => $request->assignedBy,
                ]
            );

            return PermissionAssignResult::success(
                userId: $request->userId,
                permissionId: $permissionId,
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to assign permission', [
                'user_id' => $request->userId,
                'permission' => $request->permission,
                'error' => $e->getMessage(),
            ]);

            return PermissionAssignResult::failure(
                message: 'Failed to assign permission: ' . $e->getMessage()
            );
        }
    }

    public function revoke(PermissionRevokeRequest $request): PermissionRevokeResult
    {
        $this->logger->info('Revoking permission', [
            'user_id' => $request->userId,
            'permission' => $request->permission,
            'tenant_id' => $request->tenantId,
        ]);

        try {
            $this->permissionRevoker->revokePermission(
                userId: $request->userId,
                permission: $request->permission,
                tenantId: $request->tenantId,
            );

            $this->auditLogger->log(
                'permission.revoked',
                $request->userId,
                [
                    'permission' => $request->permission,
                    'tenant_id' => $request->tenantId,
                    'revoked_by' => $request->revokedBy,
                ]
            );

            return PermissionRevokeResult::success(
                userId: $request->userId,
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to revoke permission', [
                'user_id' => $request->userId,
                'permission' => $request->permission,
                'error' => $e->getMessage(),
            ]);

            return PermissionRevokeResult::failure(
                message: 'Failed to revoke permission: ' . $e->getMessage()
            );
        }
    }

    public function hasPermission(string $userId, string $permission, string $tenantId): bool
    {
        return $this->permissionChecker->check($userId, $permission, $tenantId);
    }

    public function getUserPermissions(string $userId, string $tenantId): array
    {
        return $this->permissionChecker->getAll($userId, $tenantId);
    }

    public function getUserRoles(string $userId, string $tenantId): array
    {
        return $this->permissionChecker->getRoles($userId, $tenantId);
    }

    public function assignRole(string $userId, string $roleId, string $tenantId, string $assignedBy): bool
    {
        try {
            $this->roleAssigner->assignRole($userId, $roleId, $tenantId);

            $this->auditLogger->log(
                'role.assigned',
                $userId,
                [
                    'role_id' => $roleId,
                    'tenant_id' => $tenantId,
                    'assigned_by' => $assignedBy,
                ]
            );

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to assign role', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function revokeRole(string $userId, string $roleId, string $tenantId, string $revokedBy): bool
    {
        try {
            $this->roleRevoker->revokeRole($userId, $roleId, $tenantId);

            $this->auditLogger->log(
                'role.revoked',
                $userId,
                [
                    'role_id' => $roleId,
                    'tenant_id' => $tenantId,
                    'revoked_by' => $revokedBy,
                ]
            );

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to revoke role', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
