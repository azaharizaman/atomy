<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Identity\Contracts\RoleInterface;
use Nexus\Identity\Contracts\PermissionInterface;
use Nexus\Identity\Exceptions\UserNotFoundException;
use Nexus\Identity\ValueObjects\UserStatus;

/**
 * Database user repository implementation
 */
final readonly class DbUserRepository implements UserRepositoryInterface
{
    public function findById(string $id): UserInterface
    {
        $user = User::find($id);

        if (!$user) {
            throw new UserNotFoundException($id);
        }

        return $user;
    }

    public function findByEmail(string $email): UserInterface
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new UserNotFoundException($email);
        }

        return $user;
    }

    public function findByEmailOrNull(string $email): ?UserInterface
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data): UserInterface
    {
        return User::create($data);
    }

    public function update(string $id, array $data): UserInterface
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user->fresh();
    }

    public function delete(string $id): bool
    {
        $user = User::findOrFail($id);
        return (bool) $user->delete();
    }

    public function emailExists(string $email, ?string $excludeUserId = null): bool
    {
        $query = User::where('email', $email);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        return $query->exists();
    }

    public function getUserRoles(string $userId): array
    {
        $user = User::with('roles')->findOrFail($userId);
        return $user->roles->all();
    }

    public function getUserPermissions(string $userId): array
    {
        $user = User::with('permissions')->findOrFail($userId);
        return $user->permissions->all();
    }

    public function assignRole(string $userId, string $roleId): void
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);

        if (!$user->roles()->where('role_id', $roleId)->exists()) {
            $user->roles()->attach($roleId);
        }
    }

    public function revokeRole(string $userId, string $roleId): void
    {
        $user = User::findOrFail($userId);
        $user->roles()->detach($roleId);
    }

    public function assignPermission(string $userId, string $permissionId): void
    {
        $user = User::findOrFail($userId);
        $permission = Permission::findOrFail($permissionId);

        if (!$user->permissions()->where('permission_id', $permissionId)->exists()) {
            $user->permissions()->attach($permissionId);
        }
    }

    public function revokePermission(string $userId, string $permissionId): void
    {
        $user = User::findOrFail($userId);
        $user->permissions()->detach($permissionId);
    }

    public function findByStatus(string $status): array
    {
        return User::where('status', $status)->get()->all();
    }

    public function findByRole(string $roleId): array
    {
        return User::whereHas('roles', function ($query) use ($roleId) {
            $query->where('role_id', $roleId);
        })->get()->all();
    }

    public function search(array $criteria): array
    {
        $query = User::query();

        if (isset($criteria['email'])) {
            $query->where('email', 'like', "%{$criteria['email']}%");
        }

        if (isset($criteria['name'])) {
            $query->where('name', 'like', "%{$criteria['name']}%");
        }

        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (isset($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->get()->all();
    }

    public function updateLastLogin(string $userId): void
    {
        User::where('id', $userId)->update([
            'last_login_at' => now(),
        ]);
    }

    public function incrementFailedLoginAttempts(string $userId): int
    {
        $user = User::findOrFail($userId);
        $user->increment('failed_login_attempts');
        return $user->fresh()->failed_login_attempts;
    }

    public function resetFailedLoginAttempts(string $userId): void
    {
        User::where('id', $userId)->update([
            'failed_login_attempts' => 0,
        ]);
    }

    public function lockAccount(string $userId, string $reason): void
    {
        $user = User::findOrFail($userId);
        $metadata = $user->metadata ?? [];
        $metadata['lock_reason'] = $reason;
        $metadata['locked_at'] = now()->format('c');
        
        $user->update([
            'status' => UserStatus::LOCKED->value,
            'metadata' => $metadata,
        ]);
    }

    public function unlockAccount(string $userId): void
    {
        User::where('id', $userId)->update([
            'status' => UserStatus::ACTIVE->value,
        ]);
    }
}
