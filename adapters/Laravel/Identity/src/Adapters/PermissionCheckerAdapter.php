<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Adapters;

use Nexus\Identity\Contracts\PermissionCheckerInterface;
use Nexus\Identity\Contracts\PermissionInterface;
use Nexus\Identity\Contracts\PermissionRepositoryInterface;
use Nexus\Identity\Contracts\RoleInterface;
use Nexus\Identity\Contracts\RoleRepositoryInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Psr\Log\LoggerInterface;

final class PermissionCheckerAdapter implements PermissionCheckerInterface
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function hasPermission(UserInterface $user, string $permission): bool
    {
        $requestedPermission = $this->normalizeName($permission);
        if ($requestedPermission === '') {
            return false;
        }

        $tenantId = $this->normalizeTenantId($user->getTenantId());
        if ($tenantId === null) {
            $this->logger->warning('Permission check skipped because tenant scope is missing', [
                'user_id' => $user->getId(),
                'permission' => $requestedPermission,
            ]);

            return false;
        }

        $grantedPermissions = array_map(
            fn (PermissionInterface $granted): string => $this->normalizeName($granted->getName()),
            $this->getUserPermissions($user),
        );

        if ($this->matchesGrantedPermissions($requestedPermission, $grantedPermissions)) {
            return true;
        }

        try {
            $matchingPermissions = $this->permissionRepository->findMatching($requestedPermission);
        } catch (\Throwable $exception) {
            $this->logger->debug('Permission repository matching unavailable', [
                'permission' => $requestedPermission,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        foreach ($matchingPermissions as $candidate) {
            if (! $candidate instanceof PermissionInterface) {
                continue;
            }

            if ($this->matchesGrantedPermissions($this->normalizeName($candidate->getName()), $grantedPermissions)) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyPermission(UserInterface $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (is_string($permission) && $this->hasPermission($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllPermissions(UserInterface $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! is_string($permission) || ! $this->hasPermission($user, $permission)) {
                return false;
            }
        }

        return true;
    }

    public function hasRole(UserInterface $user, string $roleName): bool
    {
        $requestedRole = $this->normalizeName($roleName);
        if ($requestedRole === '') {
            return false;
        }

        foreach ($this->getExpandedRoles($user) as $role) {
            if ($this->normalizeName($role->getName()) === $requestedRole) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyRole(UserInterface $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if (is_string($role) && $this->hasRole($user, $role)) {
                return true;
            }
        }

        return false;
    }

    public function getUserPermissions(UserInterface $user): array
    {
        $tenantId = $this->normalizeTenantId($user->getTenantId());
        if ($tenantId === null) {
            return [];
        }

        $permissionsByName = [];

        foreach ($this->userRepository->getUserPermissions($user->getId(), $tenantId) as $permission) {
            if (! $permission instanceof PermissionInterface) {
                continue;
            }

            $permissionsByName[$this->normalizeName($permission->getName())] = $permission;
        }

        foreach ($this->getExpandedRoles($user) as $role) {
            foreach ($this->roleRepository->getRolePermissions($role->getId()) as $permission) {
                if (! $permission instanceof PermissionInterface) {
                    continue;
                }

                $permissionsByName[$this->normalizeName($permission->getName())] = $permission;
            }
        }

        unset($permissionsByName['']);

        return array_values($permissionsByName);
    }

    public function isSuperAdmin(UserInterface $user): bool
    {
        if ($this->hasRole($user, 'super-admin') || $this->hasRole($user, 'super_admin')) {
            return true;
        }

        foreach ($this->getUserPermissions($user) as $permission) {
            if ($this->normalizeName($permission->getName()) === '*') {
                return true;
            }
        }

        return false;
    }

    public function clearCache(string $userId): void
    {
        $this->logger->debug('Permission checker has no local cache to clear', [
            'user_id' => $userId,
        ]);
    }

    /**
     * @return array<int, RoleInterface>
     */
    private function getExpandedRoles(UserInterface $user): array
    {
        $tenantId = $this->normalizeTenantId($user->getTenantId());
        if ($tenantId === null) {
            return [];
        }

        $roles = $this->userRepository->getUserRoles($user->getId(), $tenantId);
        $hierarchy = $this->roleRepository->getRoleHierarchy($tenantId);
        $expandedRoles = [];
        $visited = [];

        foreach ($roles as $role) {
            if (! $role instanceof RoleInterface || ! $this->roleBelongsToTenant($role, $tenantId)) {
                continue;
            }

            $currentRole = $role;
            while ($currentRole instanceof RoleInterface) {
                $roleId = $currentRole->getId();
                if ($roleId === '' || isset($visited[$roleId])) {
                    break;
                }

                $visited[$roleId] = true;
                $expandedRoles[] = $currentRole;

                $parentRoleId = $hierarchy[$roleId] ?? $currentRole->getParentRoleId();
                if (! is_string($parentRoleId) || trim($parentRoleId) === '') {
                    break;
                }

                try {
                    $parentRole = $this->roleRepository->findById($parentRoleId);
                } catch (\Throwable $exception) {
                    $this->logger->debug('Skipping missing parent role during permission expansion', [
                        'role_id' => $roleId,
                        'parent_role_id' => $parentRoleId,
                        'error' => $exception->getMessage(),
                    ]);
                    break;
                }

                if (! $this->roleBelongsToTenant($parentRole, $tenantId)) {
                    break;
                }

                $currentRole = $parentRole;
            }
        }

        return $expandedRoles;
    }

    /**
     * @param array<int, string> $grantedPermissions
     */
    private function matchesGrantedPermissions(string $requestedPermission, array $grantedPermissions): bool
    {
        if (in_array('*', $grantedPermissions, true)) {
            return true;
        }

        if (in_array($requestedPermission, $grantedPermissions, true)) {
            return true;
        }

        $separatorPosition = strpos($requestedPermission, '.');
        if ($separatorPosition === false) {
            return false;
        }

        $resourceWildcard = substr($requestedPermission, 0, $separatorPosition) . '.*';

        return in_array($resourceWildcard, $grantedPermissions, true);
    }

    private function normalizeName(string $value): string
    {
        return strtolower(trim($value));
    }

    private function normalizeTenantId(?string $tenantId): ?string
    {
        if ($tenantId === null) {
            return null;
        }

        $normalized = trim($tenantId);

        return $normalized === '' ? null : $normalized;
    }

    private function roleBelongsToTenant(RoleInterface $role, string $tenantId): bool
    {
        $roleTenantId = $role->getTenantId();

        return $roleTenantId === null || trim($roleTenantId) === $tenantId;
    }
}
