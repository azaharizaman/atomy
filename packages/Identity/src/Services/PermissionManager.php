<?php

declare(strict_types=1);

namespace Nexus\Identity\Services;

use Nexus\Identity\Contracts\PermissionInterface;
use Nexus\Identity\Exceptions\PermissionNotFoundException;
use Nexus\Identity\Contracts\PermissionRepositoryInterface;
use Nexus\Identity\ValueObjects\Permission as PermissionValue;

/**
 * Permission manager service
 * 
 * Handles permission management operations
 */
final readonly class PermissionManager
{
    public function __construct(
        private PermissionRepositoryInterface $permissionRepository
    ) {
    }

    /**
     * Create a new permission
     * 
     * @param array{name: string, resource: string, action: string, description?: string} $data
     */
    public function createPermission(array $data): PermissionInterface
    {
        // Validate permission format
        $permissionValue = PermissionValue::fromName($data['name']);

        // Check if permission name already exists
        if ($this->permissionRepository->nameExists($data['name'])) {
            throw new \InvalidArgumentException("Permission already exists: {$data['name']}");
        }

        // Ensure resource and action match the name
        $data['resource'] = $permissionValue->resource;
        $data['action'] = $permissionValue->action;

        return $this->permissionRepository->create($data);
    }

    /**
     * Update a permission
     * 
     * @param string $permissionId Permission identifier
     * @param array<string, mixed> $data Updated permission data
     * @throws PermissionNotFoundException
     */
    public function updatePermission(string $permissionId, array $data): PermissionInterface
    {
        // Check if permission exists
        $permission = $this->permissionRepository->findById($permissionId);

        // Check name uniqueness if name is being changed
        if (isset($data['name']) && $data['name'] !== $permission->getName()) {
            if ($this->permissionRepository->nameExists($data['name'], $permissionId)) {
                throw new \InvalidArgumentException("Permission already exists: {$data['name']}");
            }

            // Update resource and action if name changed
            $permissionValue = PermissionValue::fromName($data['name']);
            $data['resource'] = $permissionValue->resource;
            $data['action'] = $permissionValue->action;
        }

        return $this->permissionRepository->update($permissionId, $data);
    }

    /**
     * Delete a permission
     * 
     * @throws PermissionNotFoundException
     */
    public function deletePermission(string $permissionId): void
    {
        $this->permissionRepository->delete($permissionId);
    }

    /**
     * Get all permissions
     * 
     * @return PermissionInterface[]
     */
    public function getAllPermissions(): array
    {
        return $this->permissionRepository->getAll();
    }

    /**
     * Get permissions by resource
     * 
     * @return PermissionInterface[]
     */
    public function getPermissionsByResource(string $resource): array
    {
        return $this->permissionRepository->findByResource($resource);
    }

    /**
     * Find a permission by ID
     * 
     * @throws PermissionNotFoundException
     */
    public function findPermission(string $permissionId): PermissionInterface
    {
        return $this->permissionRepository->findById($permissionId);
    }

    /**
     * Find a permission by name
     * 
     * @throws PermissionNotFoundException
     */
    public function findPermissionByName(string $name): PermissionInterface
    {
        return $this->permissionRepository->findByName($name);
    }

    /**
     * Find permissions matching a pattern (including wildcards)
     * 
     * @return PermissionInterface[]
     */
    public function findMatchingPermissions(string $permissionName): array
    {
        return $this->permissionRepository->findMatching($permissionName);
    }
}
