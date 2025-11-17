<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * Permission repository interface
 * 
 * Handles persistence operations for permissions
 */
interface PermissionRepositoryInterface
{
    /**
     * Find a permission by its unique identifier
     * 
     * @throws \Nexus\Identity\Exceptions\PermissionNotFoundException
     */
    public function findById(string $id): PermissionInterface;

    /**
     * Find a permission by its name
     * 
     * @throws \Nexus\Identity\Exceptions\PermissionNotFoundException
     */
    public function findByName(string $name): PermissionInterface;

    /**
     * Find a permission by name or return null
     */
    public function findByNameOrNull(string $name): ?PermissionInterface;

    /**
     * Create a new permission
     * 
     * @param array<string, mixed> $data Permission data
     */
    public function create(array $data): PermissionInterface;

    /**
     * Update an existing permission
     * 
     * @param string $id Permission identifier
     * @param array<string, mixed> $data Updated permission data
     */
    public function update(string $id, array $data): PermissionInterface;

    /**
     * Delete a permission
     */
    public function delete(string $id): bool;

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
