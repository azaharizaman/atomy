<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Services;

use Nexus\Domain\Identity\Contracts\RoleInterface;
use Nexus\Domain\Identity\Contracts\RoleRepositoryInterface;
use Nexus\Domain\Identity\Contracts\PermissionRepositoryInterface;
use Nexus\Domain\Identity\Exceptions\RoleNotFoundException;
use Nexus\Domain\Identity\Exceptions\RoleInUseException;

/**
 * Role manager service
 * 
 * Handles role management operations
 */
final readonly class RoleManager
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private PermissionRepositoryInterface $permissionRepository
    ) {
    }

    /**
     * Create a new role
     * 
     * @param array{name: string, description?: string, tenant_id?: string, parent_role_id?: string, requires_mfa?: bool} $data
     */
    public function createRole(array $data): RoleInterface
    {
        // Check if role name already exists
        if ($this->roleRepository->nameExists($data['name'], $data['tenant_id'] ?? null)) {
            throw new \InvalidArgumentException("Role name already exists: {$data['name']}");
        }

        return $this->roleRepository->create($data);
    }

    /**
     * Update a role
     * 
     * @param string $roleId Role identifier
     * @param array<string, mixed> $data Updated role data
     * @throws RoleNotFoundException
     */
    public function updateRole(string $roleId, array $data): RoleInterface
    {
        // Check if role exists
        $role = $this->roleRepository->findById($roleId);

        // Check name uniqueness if name is being changed
        if (isset($data['name']) && $data['name'] !== $role->getName()) {
            if ($this->roleRepository->nameExists($data['name'], $role->getTenantId(), $roleId)) {
                throw new \InvalidArgumentException("Role name already exists: {$data['name']}");
            }
        }

        return $this->roleRepository->update($roleId, $data);
    }

    /**
     * Delete a role
     * 
     * @throws RoleNotFoundException
     * @throws RoleInUseException
     */
    public function deleteRole(string $roleId): void
    {
        $role = $this->roleRepository->findById($roleId);

        // Prevent deletion of system roles
        if ($role->isSystemRole()) {
            throw new \InvalidArgumentException('Cannot delete system-defined role');
        }

        // Check if role has users
        if ($this->roleRepository->hasUsers($roleId)) {
            $userCount = $this->roleRepository->countUsers($roleId);
            throw new RoleInUseException($roleId, $userCount);
        }

        $this->roleRepository->delete($roleId);
    }

    /**
     * Assign a permission to a role
     * 
     * @throws RoleNotFoundException
     */
    public function assignPermission(string $roleId, string $permissionId): void
    {
        $this->roleRepository->findById($roleId); // Verify role exists
        $this->permissionRepository->findById($permissionId); // Verify permission exists
        $this->roleRepository->assignPermission($roleId, $permissionId);
    }

    /**
     * Revoke a permission from a role
     * 
     * @throws RoleNotFoundException
     */
    public function revokePermission(string $roleId, string $permissionId): void
    {
        $this->roleRepository->findById($roleId); // Verify role exists
        $this->roleRepository->revokePermission($roleId, $permissionId);
    }

    /**
     * Get all roles
     * 
     * @return RoleInterface[]
     */
    public function getAllRoles(?string $tenantId = null): array
    {
        return $this->roleRepository->getAll($tenantId);
    }

    /**
     * Find a role by ID
     * 
     * @throws RoleNotFoundException
     */
    public function findRole(string $roleId): RoleInterface
    {
        return $this->roleRepository->findById($roleId);
    }

    /**
     * Find a role by name
     * 
     * @throws RoleNotFoundException
     */
    public function findRoleByName(string $name, ?string $tenantId = null): RoleInterface
    {
        return $this->roleRepository->findByName($name, $tenantId);
    }
}
