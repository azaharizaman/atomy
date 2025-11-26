<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * Role persist interface (CQRS Write Model)
 *
 * Handles write operations for roles.
 * Consuming applications provide concrete implementations.
 */
interface RolePersistInterface
{
    /**
     * Create a new role
     *
     * @param array<string, mixed> $data Role data
     */
    public function create(array $data): RoleInterface;

    /**
     * Update an existing role
     *
     * @param string $id Role identifier
     * @param array<string, mixed> $data Updated role data
     */
    public function update(string $id, array $data): RoleInterface;

    /**
     * Delete a role
     *
     * @throws \Nexus\Identity\Exceptions\RoleInUseException If role is assigned to users
     */
    public function delete(string $id): bool;

    /**
     * Assign a permission to a role
     */
    public function assignPermission(string $roleId, string $permissionId): void;

    /**
     * Revoke a permission from a role
     */
    public function revokePermission(string $roleId, string $permissionId): void;
}
