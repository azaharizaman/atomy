<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * Role query interface (CQRS Read Model)
 *
 * Handles read-only operations for roles.
 * Consuming applications provide concrete implementations.
 */
interface RoleQueryInterface
{
    /**
     * Find a role by its unique identifier
     *
     * @throws \Nexus\Identity\Exceptions\RoleNotFoundException
     */
    public function findById(string $id): RoleInterface;

    /**
     * Find a role by its name
     *
     * @throws \Nexus\Identity\Exceptions\RoleNotFoundException
     */
    public function findByName(string $name, ?string $tenantId = null): RoleInterface;

    /**
     * Find a role by name or return null
     */
    public function findByNameOrNull(string $name, ?string $tenantId = null): ?RoleInterface;

    /**
     * Check if a role name is already in use
     *
     * @param string $name Role name to check
     * @param string|null $tenantId Tenant ID for scoping
     * @param string|null $excludeRoleId Role ID to exclude from check (for updates)
     */
    public function nameExists(string $name, ?string $tenantId = null, ?string $excludeRoleId = null): bool;

    /**
     * Get all permissions assigned to a role
     *
     * @return PermissionInterface[]
     */
    public function getRolePermissions(string $roleId): array;

    /**
     * Get all roles
     *
     * @return RoleInterface[]
     */
    public function getAll(?string $tenantId = null): array;

    /**
     * Get role hierarchy (parent-child relationships)
     *
     * @return array<string, string> Map of role ID to parent role ID
     */
    public function getRoleHierarchy(?string $tenantId = null): array;

    /**
     * Check if a role has any users assigned
     */
    public function hasUsers(string $roleId): bool;

    /**
     * Count users assigned to a role
     */
    public function countUsers(string $roleId): int;
}
