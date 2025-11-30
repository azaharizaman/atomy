<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * Permission checker interface
 * 
 * Handles authorization checks for users
 */
interface PermissionCheckerInterface
{
    /**
     * Check if a user has a specific permission
     * 
     * @param UserInterface $user User to check
     * @param string $permission Permission name (e.g., "users.create")
     * @return bool True if user has permission
     */
    public function hasPermission(UserInterface $user, string $permission): bool;

    /**
     * Check if a user has any of the given permissions
     * 
     * @param UserInterface $user User to check
     * @param string[] $permissions Array of permission names
     * @return bool True if user has at least one permission
     */
    public function hasAnyPermission(UserInterface $user, array $permissions): bool;

    /**
     * Check if a user has all of the given permissions
     * 
     * @param UserInterface $user User to check
     * @param string[] $permissions Array of permission names
     * @return bool True if user has all permissions
     */
    public function hasAllPermissions(UserInterface $user, array $permissions): bool;

    /**
     * Check if a user has a specific role
     * 
     * @param UserInterface $user User to check
     * @param string $roleName Role name
     * @return bool True if user has role
     */
    public function hasRole(UserInterface $user, string $roleName): bool;

    /**
     * Check if a user has any of the given roles
     * 
     * @param UserInterface $user User to check
     * @param string[] $roles Array of role names
     * @return bool True if user has at least one role
     */
    public function hasAnyRole(UserInterface $user, array $roles): bool;

    /**
     * Get all permissions for a user (from roles and direct assignments)
     * 
     * @param UserInterface $user User
     * @return PermissionInterface[] Array of permissions
     */
    public function getUserPermissions(UserInterface $user): array;

    /**
     * Check if user is super admin (bypasses all permission checks)
     */
    public function isSuperAdmin(UserInterface $user): bool;

    /**
     * Clear cached permissions for a user
     */
    public function clearCache(string $userId): void;
}
