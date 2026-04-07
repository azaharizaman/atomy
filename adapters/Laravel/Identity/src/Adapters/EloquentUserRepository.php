<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Adapters;

use App\Models\User as UserModel;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Laravel\Identity\Mappers\LaravelUserMapper;

final readonly class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(string $id): UserInterface
    {
        $user = UserModel::query()->findOrFail($id);
        return LaravelUserMapper::fromModel($user);
    }

    public function findByEmail(string $email): UserInterface
    {
        $user = UserModel::query()->where('email', $email)->firstOrFail();
        return LaravelUserMapper::fromModel($user);
    }

    public function findByEmailOrNull(string $email): ?UserInterface
    {
        $user = UserModel::query()->where('email', $email)->first();
        return $user ? LaravelUserMapper::fromModel($user) : null;
    }

    public function emailExists(string $email, ?string $excludeUserId = null): bool
    {
        $query = UserModel::query()->where('email', $email);
        if ($excludeUserId) {
            $query->whereKeyNot($excludeUserId);
        }
        return $query->exists();
    }

    public function getUserRoles(string $userId, ?string $tenantId = null): array
    {
        // TODO: Implement based on project requirements (e.g., pivot tables)
        return [];
    }

    public function getUserPermissions(string $userId, ?string $tenantId = null): array
    {
        // TODO: Implement based on project requirements
        return [];
    }

    public function findByStatus(string $status, string $tenantId): array
    {
        return UserModel::query()
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get()
            ->map(fn (UserModel $u) => LaravelUserMapper::fromModel($u))
            ->all();
    }

    public function findByRole(string $roleId, string $tenantId): array
    {
        return UserModel::query()
            ->where('tenant_id', $tenantId)
            ->where('role', $roleId)
            ->get()
            ->map(fn (UserModel $u) => LaravelUserMapper::fromModel($u))
            ->all();
    }

    public function search(array $criteria): array
    {
        // TODO: Implement search
        return [];
    }

    public function create(array $data): UserInterface
    {
        $user = UserModel::query()->create($data);
        return LaravelUserMapper::fromModel($user);
    }

    public function update(string $id, array $data): UserInterface
    {
        $user = UserModel::query()->findOrFail($id);
        $user->update($data);
        return LaravelUserMapper::fromModel($user);
    }

    public function delete(string $id, ?string $tenantId = null): bool
    {
        $query = UserModel::query()->whereKey($id);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        return (bool) $query->delete();
    }

    public function assignRole(string $userId, string $roleId, ?string $tenantId = null): void
    {
        // TODO
    }

    public function revokeRole(string $userId, string $roleId, ?string $tenantId = null): void
    {
        // TODO
    }

    public function assignPermission(string $userId, string $permissionId, ?string $tenantId = null): void
    {
        // TODO
    }

    public function revokePermission(string $userId, string $permissionId, ?string $tenantId = null): void
    {
        // TODO
    }

    public function updateLastLogin(string $userId): void
    {
        UserModel::query()->whereKey($userId)->update(['last_login_at' => now()]);
    }

    public function incrementFailedLoginAttempts(string $userId): int
    {
        $user = UserModel::query()->findOrFail($userId);
        $user->increment('failed_login_attempts');
        return $user->failed_login_attempts;
    }

    public function resetFailedLoginAttempts(string $userId): void
    {
        UserModel::query()->whereKey($userId)->update(['failed_login_attempts' => 0]);
    }

    public function lockAccount(string $userId, string $reason): void
    {
        UserModel::query()->whereKey($userId)->update(['status' => 'locked', 'lock_reason' => $reason]);
    }

    public function unlockAccount(string $userId): void
    {
        UserModel::query()->whereKey($userId)->update(['status' => 'active', 'lock_reason' => null]);
    }
}
