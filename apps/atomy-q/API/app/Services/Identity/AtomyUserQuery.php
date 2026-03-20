<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\User as UserModel;
use Nexus\Identity\Contracts\PermissionInterface;
use Nexus\Identity\Contracts\RoleInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\Identity\Exceptions\UserNotFoundException;

/**
 * Tenant-aware reads for Nexus Identity; aligns with `users` schema (single `role`, no RBAC tables).
 */
final readonly class AtomyUserQuery implements UserQueryInterface
{
    public function findById(string $id): UserInterface
    {
        $user = UserModel::query()->whereKey($id)->first();
        if ($user === null) {
            throw new UserNotFoundException($id);
        }

        return new AtomyIdentityUser($user);
    }

    public function findByEmail(string $email): UserInterface
    {
        $normalized = strtolower(trim($email));
        $user = UserModel::query()->where('email', $normalized)->orderBy('tenant_id')->first();
        if ($user === null) {
            throw new UserNotFoundException($normalized);
        }

        return new AtomyIdentityUser($user);
    }

    public function findByEmailOrNull(string $email): ?UserInterface
    {
        $normalized = strtolower(trim($email));
        $user = UserModel::query()->where('email', $normalized)->orderBy('tenant_id')->first();

        return $user !== null ? new AtomyIdentityUser($user) : null;
    }

    public function emailExists(string $email, ?string $excludeUserId = null): bool
    {
        $normalized = strtolower(trim($email));
        $q = UserModel::query()->where('email', $normalized);
        if ($excludeUserId !== null && $excludeUserId !== '') {
            $q->where('id', '!=', $excludeUserId);
        }

        return $q->exists();
    }

    /**
     * @return RoleInterface[]
     */
    public function getUserRoles(string $userId): array
    {
        return [];
    }

    /**
     * @return PermissionInterface[]
     */
    public function getUserPermissions(string $userId): array
    {
        return [];
    }

    /**
     * @return UserInterface[]
     */
    public function findByStatus(string $status): array
    {
        return UserModel::query()
            ->where('status', $status)
            ->get()
            ->map(fn (UserModel $u): UserInterface => new AtomyIdentityUser($u))
            ->all();
    }

    /**
     * @return UserInterface[]
     */
    public function findByRole(string $roleId): array
    {
        return UserModel::query()
            ->where('role', $roleId)
            ->get()
            ->map(fn (UserModel $u): UserInterface => new AtomyIdentityUser($u))
            ->all();
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return UserInterface[]
     */
    public function search(array $criteria): array
    {
        $q = UserModel::query();
        if (isset($criteria['tenant_id']) && is_string($criteria['tenant_id']) && $criteria['tenant_id'] !== '') {
            $q->where('tenant_id', $criteria['tenant_id']);
        }
        if (isset($criteria['email']) && is_string($criteria['email']) && $criteria['email'] !== '') {
            $q->where('email', 'like', '%' . $criteria['email'] . '%');
        }
        if (isset($criteria['status']) && is_string($criteria['status']) && $criteria['status'] !== '') {
            $q->where('status', $criteria['status']);
        }

        return $q->orderBy('email')
            ->limit(100)
            ->get()
            ->map(fn (UserModel $u): UserInterface => new AtomyIdentityUser($u))
            ->all();
    }
}
