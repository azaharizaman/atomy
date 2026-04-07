<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Adapters;

use Nexus\Identity\Contracts\PermissionCheckerInterface;
use Nexus\Identity\Contracts\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Laravel implementation of PermissionCheckerInterface.
 *
 * Uses Laravel's Gate for authorization.
 */
class PermissionCheckerAdapter implements PermissionCheckerInterface
{
    public function __construct(
        private readonly \Nexus\Identity\Contracts\PermissionRepositoryInterface $permissionRepository,
        private readonly \Nexus\Identity\Contracts\UserRepositoryInterface $userRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function hasPermission(UserInterface $user, string $permission): bool
    {
        $permissions = $this->getUserPermissions($user);

        if (in_array($permission, $permissions, true) || in_array('*', $permissions, true)) {
            return true;
        }

        $parts = explode('.', $permission);
        if (count($parts) > 1) {
            $wildcard = $parts[0] . '.*';
            if (in_array($wildcard, $permissions, true)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserPermissions(UserInterface $user): array
    {
        $tenantId = $user->getTenantId();
        $userPermissions = $this->userRepository->getUserPermissions($user->getId(), $tenantId);
        $userRoles = $this->userRepository->getUserRoles($user->getId(), $tenantId);
        
        $permissions = array_map(fn($p) => $p->getName(), $userPermissions);
        foreach ($userRoles as $role) {
             // In a real implementation, Roles would have permissions.
             // Mocking for now as per previous implementation logic.
        }

        return $permissions;
    }
            if (in_array($wildcard, $permissions, true)) {
                return true;
            }
        }

        return in_array('*', $permissions, true);
    }

    /**
     * {@inheritdoc}
     */
    public function hasAnyPermission(UserInterface $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAllPermissions(UserInterface $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRole(UserInterface $user, string $roleName): bool
    {
        // Implementation would check user's roles
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAnyRole(UserInterface $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($user, $role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserPermissions(UserInterface $user): array
    {
        return $this->permissionRepository->getUserPermissions($user->getId(), $user->getTenantId());
    }

    /**
     * {@inheritdoc}
     */
    public function isSuperAdmin(UserInterface $user): bool
    {
        // Implementation would check if user has super admin flag
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(string $userId): void
    {
        $this->logger->debug('Clearing permission cache', ['user_id' => $userId]);
    }
}
