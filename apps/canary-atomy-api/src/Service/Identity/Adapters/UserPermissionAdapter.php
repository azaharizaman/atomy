<?php

declare(strict_types=1);

namespace App\Service\Identity\Adapters;

use App\Repository\UserRepository;
use Nexus\IdentityOperations\Services\PermissionAssignerInterface;
use Nexus\IdentityOperations\Services\PermissionRevokerInterface;
use Nexus\IdentityOperations\Services\PermissionCheckerInterface;
use Nexus\IdentityOperations\Services\RoleAssignerInterface;
use Nexus\IdentityOperations\Services\RoleRevokerInterface;
use Nexus\IdentityOperations\DTOs\PermissionDto;
use Nexus\IdentityOperations\DTOs\RoleDto;

final readonly class UserPermissionAdapter implements PermissionAssignerInterface, PermissionRevokerInterface, PermissionCheckerInterface, RoleAssignerInterface, RoleRevokerInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function assignPermission(string $userId, string $permission, string $tenantId, ?\DateTimeInterface $expiresAt = null): string
    {
        return 'perm-' . $permission;
    }

    public function revokePermission(string $userId, string $permission, ?string $tenantId = null): void
    {
        // No-op
    }

    public function check(string $userId, string $permission, ?string $tenantId = null): bool
    {
        return true;
    }

    public function getAll(string $userId, ?string $tenantId = null): array
    {
        return [
            new PermissionDto('view_dashboard', 'View Dashboard'),
            new PermissionDto('manage_users', 'Manage Users'),
        ];
    }

    public function getRoles(string $userId, ?string $tenantId = null): array
    {
        $user = $this->userRepository->find($userId);
        if (!$user) return [];

        return array_map(fn($role) => new RoleDto($role, $role), $user->getRoles());
    }

    public function assignRole(string $userId, string $roleId, string $tenantId): string
    {
        $user = $this->userRepository->find($userId);
        if ($user) {
            $roles = $user->getRoles();
            if (!in_array($roleId, $roles, true)) {
                $roles[] = $roleId;
                $user->setRoles($roles);
                $this->userRepository->save($user);
            }
        }
        return $roleId;
    }

    public function revokeRole(string $userId, string $roleId, string $tenantId): void
    {
        $user = $this->userRepository->find($userId);
        if ($user) {
            $roles = $user->getRoles();
            $roles = array_filter($roles, fn($r) => $r !== $roleId);
            $user->setRoles(array_values($roles));
            $this->userRepository->save($user);
        }
    }
}
