<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

use Nexus\IdentityOperations\DTOs\PermissionAssignRequest;
use Nexus\IdentityOperations\DTOs\PermissionAssignResult;
use Nexus\IdentityOperations\DTOs\PermissionRevokeRequest;
use Nexus\IdentityOperations\DTOs\PermissionRevokeResult;

/**
 * Service interface for user permission operations.
 */
interface UserPermissionServiceInterface
{
    /**
     * Assign a permission to a user.
     */
    public function assign(PermissionAssignRequest $request): PermissionAssignResult;

    /**
     * Revoke a permission from a user.
     */
    public function revoke(PermissionRevokeRequest $request): PermissionRevokeResult;

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $userId, string $permission, ?string $tenantId = null): bool;

    /**
     * Get all permissions for a user.
     */
    public function getUserPermissions(string $userId, ?string $tenantId = null): array;

    /**
     * Get all roles for a user.
     */
    public function getUserRoles(string $userId, ?string $tenantId = null): array;

    /**
     * Assign a role to a user.
     */
    public function assignRole(string $userId, string $roleId, string $tenantId, string $assignedBy): bool;

    /**
     * Revoke a role from a user.
     */
    public function revokeRole(string $userId, string $roleId, string $tenantId, string $revokedBy): bool;
}
