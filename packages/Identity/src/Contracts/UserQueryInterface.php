<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * User query interface (CQRS Read Model)
 *
 * Handles read-only operations for users.
 * Consuming applications provide concrete implementations.
 */
interface UserQueryInterface
{
    /**
     * Find a user by their unique identifier
     *
     * @param string $id User identifier
     * @param string|null $tenantId Tenant ID for tenant-scoped queries
     * @throws \Nexus\Identity\Exceptions\UserNotFoundException
     */
    public function findById(string $id, ?string $tenantId = null): UserInterface;

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
     * Check if an email address is already in use
     *
     * @param string $email Email address to check
     * @param string|null $excludeUserId User ID to exclude from check (for updates)
     * @param string|null $tenantId Tenant ID for tenant-scoped check
     */
    public function emailExists(string $email, ?string $excludeUserId = null, ?string $tenantId = null): bool;

    /**
     * Get all roles assigned to a user
     *
     * @param string $userId User ID
     * @param string|null $tenantId Tenant ID for tenant-scoped queries
     * @return RoleInterface[]
     */
    public function getUserRoles(string $userId, ?string $tenantId = null): array;

    /**
     * Get all direct permissions assigned to a user
     *
     * @param string $userId User ID
     * @param string|null $tenantId Tenant ID for tenant-scoped queries
     * @return PermissionInterface[]
     */
    public function getUserPermissions(string $userId, ?string $tenantId = null): array;

    /**
     * Get users by status
     *
     * @param string $status User status
     * @param string $tenantId Tenant ID (required for tenant-scoped queries)
     * @return UserInterface[]
     */
    public function findByStatus(string $status, string $tenantId): array;

    /**
     * Get users by role
     *
     * @param string $roleId Role ID
     * @param string $tenantId Tenant ID (required for tenant-scoped queries)
     * @return UserInterface[]
     */
    public function findByRole(string $roleId, string $tenantId): array;

    /**
     * Search users by query
     *
     * @param array<string, mixed> $criteria Search criteria
     * @return UserInterface[]
     */
    public function search(array $criteria): array;
}
