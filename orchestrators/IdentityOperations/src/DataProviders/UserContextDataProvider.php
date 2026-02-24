<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DataProviders;

use Nexus\IdentityOperations\Contracts\UserContextProviderInterface;
use Nexus\IdentityOperations\DTOs\UserContext;

/**
 * Data provider for user context aggregation.
 * 
 * Aggregates user data from multiple packages into a unified context.
 */
final readonly class UserContextDataProvider implements UserContextProviderInterface
{
    public function __construct(
        private UserQueryInterface $userQuery,
        private PermissionQueryInterface $permissionQuery,
    ) {}

    public function getContext(string $userId): UserContext
    {
        $user = $this->userQuery->findById($userId);
        
        if ($user === null) {
            return UserContext::anonymous();
        }

        $permissions = $this->permissionQuery->getUserPermissions($userId, $user['tenant_id'] ?? null);
        $roles = $this->permissionQuery->getUserRoles($userId, $user['tenant_id'] ?? null);

        return new UserContext(
            userId: $user['id'],
            email: $user['email'],
            firstName: $user['first_name'] ?? null,
            lastName: $user['last_name'] ?? null,
            tenantId: $user['tenant_id'] ?? null,
            status: $user['status'],
            permissions: $permissions,
            roles: $roles,
        );
    }

    public function userExists(string $userId): bool
    {
        return $this->userQuery->exists($userId);
    }

    public function isUserActive(string $userId): bool
    {
        return $this->userQuery->isActive($userId);
    }
}

/**
 * Interface for querying user data.
 */
interface UserQueryInterface
{
    /**
     * @return array{id: string, email: string, first_name: string, last_name: string, tenant_id: string|null, status: string}|null
     */
    public function findById(string $userId): ?array;

    public function exists(string $userId): bool;

    public function isActive(string $userId): bool;
}

/**
 * Interface for querying permission data.
 */
interface PermissionQueryInterface
{
    /**
     * @return array<int, string>
     */
    public function getUserPermissions(string $userId, ?string $tenantId = null): array;

    /**
     * @return array<int, string>
     */
    public function getUserRoles(string $userId, ?string $tenantId = null): array;
}
