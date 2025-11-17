<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * User repository interface
 * 
 * Handles persistence operations for users
 */
interface UserRepositoryInterface
{
    /**
     * Find a user by their unique identifier
     * 
     * @throws \Nexus\Identity\Exceptions\UserNotFoundException
     */
    public function findById(string $id): UserInterface;

    /**
     * Find a user by their email address
     * 
     * @throws \Nexus\Identity\Exceptions\UserNotFoundException
     */
    public function findByEmail(string $email): UserInterface;

    /**
     * Find a user by their email address or return null
     */
    public function findByEmailOrNull(string $email): ?UserInterface;

    /**
     * Create a new user
     * 
     * @param array<string, mixed> $data User data
     */
    public function create(array $data): UserInterface;

    /**
     * Update an existing user
     * 
     * @param string $id User identifier
     * @param array<string, mixed> $data Updated user data
     */
    public function update(string $id, array $data): UserInterface;

    /**
     * Delete a user
     */
    public function delete(string $id): bool;

    /**
     * Check if an email address is already in use
     * 
     * @param string $email Email address to check
     * @param string|null $excludeUserId User ID to exclude from check (for updates)
     */
    public function emailExists(string $email, ?string $excludeUserId = null): bool;

    /**
     * Get all roles assigned to a user
     * 
     * @return RoleInterface[]
     */
    public function getUserRoles(string $userId): array;

    /**
     * Get all direct permissions assigned to a user
     * 
     * @return PermissionInterface[]
     */
    public function getUserPermissions(string $userId): array;

    /**
     * Assign a role to a user
     */
    public function assignRole(string $userId, string $roleId): void;

    /**
     * Revoke a role from a user
     */
    public function revokeRole(string $userId, string $roleId): void;

    /**
     * Assign a permission directly to a user
     */
    public function assignPermission(string $userId, string $permissionId): void;

    /**
     * Revoke a permission from a user
     */
    public function revokePermission(string $userId, string $permissionId): void;

    /**
     * Get users by status
     * 
     * @return UserInterface[]
     */
    public function findByStatus(string $status): array;

    /**
     * Get users by role
     * 
     * @return UserInterface[]
     */
    public function findByRole(string $roleId): array;

    /**
     * Search users by query
     * 
     * @param array<string, mixed> $criteria Search criteria
     * @return UserInterface[]
     */
    public function search(array $criteria): array;

    /**
     * Update user's last login timestamp
     */
    public function updateLastLogin(string $userId): void;

    /**
     * Increment failed login attempts
     */
    public function incrementFailedLoginAttempts(string $userId): int;

    /**
     * Reset failed login attempts
     */
    public function resetFailedLoginAttempts(string $userId): void;

    /**
     * Lock a user account
     */
    public function lockAccount(string $userId, string $reason): void;

    /**
     * Unlock a user account
     */
    public function unlockAccount(string $userId): void;
}
