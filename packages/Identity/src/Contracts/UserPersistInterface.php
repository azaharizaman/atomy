<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * User persist interface (CQRS Write Model)
 *
 * Handles write operations for users.
 * Consuming applications provide concrete implementations.
 */
interface UserPersistInterface
{
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
     * Assign a role to a user
     */
    public function assignRole(string $userId, string $roleId, ?string $tenantId = null): void;

    /**
     * Revoke a role from a user
     */
    public function revokeRole(string $userId, string $roleId, ?string $tenantId = null): void;

    /**
     * Assign a permission directly to a user
     */
    public function assignPermission(string $userId, string $permissionId, ?string $tenantId = null): void;

    /**
     * Revoke a permission from a user
     */
    public function revokePermission(string $userId, string $permissionId, ?string $tenantId = null): void;

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
