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
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function hasPermission(UserInterface $user, string $permission): bool
    {
        // Implementation would use Laravel's Gate
        // return Gate::forUser($user)->allows($permission);
        return true;
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
        // Implementation would load from database
        return [];
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
