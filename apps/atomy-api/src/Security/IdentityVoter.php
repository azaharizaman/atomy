<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Nexus\Domain\Identity\Contracts\PermissionRepositoryInterface;
use Nexus\Domain\Identity\Contracts\RoleRepositoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Symfony Security Voter that integrates with Nexus Identity RBAC.
 * 
 * Checks if the authenticated user has the required permission (resource:action)
 * based on their assigned roles and the permissions attached to those roles.
 */
final class IdentityVoter extends Voter
{
    /**
     * Attribute prefix for identity permissions.
     * Format: IDENTITY_RESOURCE_ACTION (e.g., IDENTITY_USER_CREATE)
     */
    private const ATTRIBUTE_PREFIX = 'IDENTITY_';

    /**
     * Cache for user permissions (within single request).
     * @var array<string, array<string>>
     */
    private array $permissionCache = [];

    public function __construct(
        private readonly RoleRepositoryInterface $roleRepo,
        private readonly PermissionRepositoryInterface $permissionRepo,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // We support attributes like IDENTITY_USER_CREATE, IDENTITY_ROLE_DELETE, etc.
        return str_starts_with($attribute, self::ATTRIBUTE_PREFIX);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Parse the attribute: IDENTITY_USER_CREATE -> resource=user, action=create
        $permissionKey = substr($attribute, strlen(self::ATTRIBUTE_PREFIX));
        $parts = explode('_', strtolower($permissionKey), 2);
        
        if (count($parts) !== 2) {
            return false;
        }

        [$resource, $action] = $parts;

        // Super admin bypass - ROLE_SUPER_ADMIN and ROLE_ADMIN are treated as super admins
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true) || in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Check if user has the required permission
        return $this->userHasPermission($user, $resource, $action);
    }

    /**
     * Check if user has a specific permission through their roles.
     */
    private function userHasPermission(User $user, string $resource, string $action): bool
    {
        $userId = $user->getId();
        $cacheKey = "{$userId}:{$resource}:{$action}";

        // Check cache first
        if (isset($this->permissionCache[$cacheKey])) {
            return $this->permissionCache[$cacheKey];
        }

        // Get all user's role names
        $userRoles = $user->getRoles();
        
        // For each role, check if it has the required permission
        foreach ($userRoles as $roleName) {
            // Skip Symfony-specific roles (they start with ROLE_) 
            // These are handled by Symfony's native role hierarchy
            if (str_starts_with($roleName, 'ROLE_')) {
                continue;
            }
            
            // Try to find the role entity by name
            try {
                $role = $this->roleRepo->findByName($roleName);
            } catch (\Throwable $e) {
                // Role not found in database, skip
                continue;
            }
            
            if ($role === null) {
                continue;
            }

            // Get permissions for this role
            $permissions = $this->roleRepo->getRolePermissions($role->getId());
            
            foreach ($permissions as $permission) {
                if ($permission->getResource() === $resource && $permission->getAction() === $action) {
                    $this->permissionCache[$cacheKey] = true;
                    return true;
                }
            }
        }

        $this->permissionCache[$cacheKey] = false;
        return false;
    }
}
