<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Services;

use Nexus\Domain\Identity\Contracts\UserInterface;
use Nexus\Domain\Identity\Contracts\PermissionCheckerInterface;
use Nexus\Domain\Identity\Contracts\UserRepositoryInterface;
use Nexus\Domain\Identity\Contracts\RoleRepositoryInterface;
use Nexus\Domain\Identity\Contracts\PermissionInterface;
use Nexus\Domain\Identity\ValueObjects\Permission;

/**
 * Permission checker service
 * 
 * Handles authorization checks for users
 */
final readonly class PermissionChecker implements PermissionCheckerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RoleRepositoryInterface $roleRepository
    ) {
    }

    public function hasPermission(UserInterface $user, string $permission): bool
    {
        // Super admin bypasses all checks
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $allPermissions = $this->getUserPermissions($user);
        $targetPermission = Permission::fromName($permission);

        foreach ($allPermissions as $userPermission) {
            if ($userPermission->matches($permission)) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyPermission(UserInterface $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllPermissions(UserInterface $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                return false;
            }
        }

        return true;
    }

    public function hasRole(UserInterface $user, string $roleName): bool
    {
        $userRoles = $this->userRepository->getUserRoles($user->getId());

        foreach ($userRoles as $role) {
            if ($role->getName() === $roleName) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyRole(UserInterface $user, array $roles): bool
    {
        foreach ($roles as $roleName) {
            if ($this->hasRole($user, $roleName)) {
                return true;
            }
        }

        return false;
    }

    public function getUserPermissions(UserInterface $user): array
    {
        $permissions = [];

        // Get direct user permissions
        $directPermissions = $this->userRepository->getUserPermissions($user->getId());
        foreach ($directPermissions as $permission) {
            $permissions[$permission->getName()] = $permission;
        }

        // Get permissions from roles
        $userRoles = $this->userRepository->getUserRoles($user->getId());
        foreach ($userRoles as $role) {
            $rolePermissions = $this->roleRepository->getRolePermissions($role->getId());
            foreach ($rolePermissions as $permission) {
                $permissions[$permission->getName()] = $permission;
            }
        }

        return array_values($permissions);
    }

    public function isSuperAdmin(UserInterface $user): bool
    {
        $userRoles = $this->userRepository->getUserRoles($user->getId());

        foreach ($userRoles as $role) {
            if ($role->isSuperAdmin()) {
                return true;
            }
        }

        return false;
    }

    public function clearCache(string $userId): void
    {
        // This method is for cache invalidation in implementations
        // In the base package, it's a no-op
    }
}
