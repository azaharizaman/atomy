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
}
