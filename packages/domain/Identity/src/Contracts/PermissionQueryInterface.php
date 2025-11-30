<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

/**
 * Permission query interface (CQRS Read Model)
 *
 * Handles read-only operations for permissions.
 * Consuming applications provide concrete implementations.
 */
interface PermissionQueryInterface
{
    /**
     * Find a permission by its unique identifier
     *
     * @throws \Nexus\Domain\Identity\Exceptions\PermissionNotFoundException
     */
    public function findById(string $id): PermissionInterface;

    /**
     * Find a permission by its name
     *
     * @throws \Nexus\Domain\Identity\Exceptions\PermissionNotFoundException
     */
    public function findByName(string $name): PermissionInterface;

    /**
     * Find a permission by name or return null
     */
    public function findByNameOrNull(string $name): ?PermissionInterface;

    /**
     * Check if a permission name is already in use
     *
     * @param string $name Permission name to check
     * @param string|null $excludePermissionId Permission ID to exclude from check (for updates)
     */
    public function nameExists(string $name, ?string $excludePermissionId = null): bool;

    /**
     * Get all permissions
     *
     * @return PermissionInterface[]
     */
    public function getAll(): array;

    /**
     * Get permissions by resource
     *
     * @return PermissionInterface[]
     */
    public function findByResource(string $resource): array;

    /**
     * Find permissions that match a given permission name (including wildcards)
     *
     * @return PermissionInterface[]
     */
    public function findMatching(string $permissionName): array;
}
