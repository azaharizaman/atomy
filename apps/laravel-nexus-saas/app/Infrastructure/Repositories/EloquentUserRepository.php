<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Models\User as UserModel;
use App\ValueObjects\PermissionVO;
use App\ValueObjects\RoleVO;
use App\ValueObjects\UserVO;
use Nexus\Identity\Contracts\PermissionInterface;
use Nexus\Identity\Contracts\RoleInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Identity\Exceptions\UserNotFoundException;

/**
 * Concrete implementation of UserRepositoryInterface using Laravel Eloquent ORM.
 *
 * Implements both UserQueryInterface (reads) and UserPersistInterface (writes)
 * through the combined UserRepositoryInterface.
 */
class EloquentUserRepository implements UserRepositoryInterface
{
    // ========================================================================
    // UserQueryInterface Methods (Read Operations)
    // ========================================================================

    /**
     * Find a user by their unique identifier
     *
     * @throws UserNotFoundException
     */
    public function findById(string $id): UserInterface
    {
        $user = UserModel::find($id);

        if (!$user) {
            throw new UserNotFoundException($id);
        }

        return $this->mapToDomainObject($user);
    }

    /**
     * Find a user by their email address
     *
     * @throws UserNotFoundException
     */
    public function findByEmail(string $email): UserInterface
    {
        $user = UserModel::where('email', $email)->first();

        if (!$user) {
            throw new UserNotFoundException($email);
        }

        return $this->mapToDomainObject($user);
    }

    /**
     * Find a user by their email address or return null
     */
    public function findByEmailOrNull(string $email): ?UserInterface
    {
        $user = UserModel::where('email', $email)->first();

        return $user ? $this->mapToDomainObject($user) : null;
    }

    /**
     * Check if an email address is already in use
     */
    public function emailExists(string $email, ?string $excludeUserId = null): bool
    {
        $query = UserModel::where('email', $email);

        if ($excludeUserId !== null) {
            $query->where('id', '!=', $excludeUserId);
        }

        return $query->exists();
    }

    /**
     * Get all roles assigned to a user
     *
     * @return RoleInterface[]
     */
    public function getUserRoles(string $userId): array
    {
        $user = UserModel::find($userId);

        if (!$user) {
            return [];
        }

        // Check if model has roles relationship (e.g., using Spatie permissions)
        if (method_exists($user, 'roles')) {
            return collect($user->roles)->map(fn($role) => RoleVO::fromArray([
                'id' => (string) $role->id,
                'name' => $role->name,
                'description' => $role->description ?? null,
                'tenant_id' => $role->tenant_id ?? null,
                'is_system_role' => $role->is_system_role ?? false,
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at,
            ]))->all();
        }

        return [];
    }

    /**
     * Get all direct permissions assigned to a user
     *
     * @return PermissionInterface[]
     */
    public function getUserPermissions(string $userId): array
    {
        $user = UserModel::find($userId);

        if (!$user) {
            return [];
        }

        // Check if model has permissions relationship (e.g., using Spatie permissions)
        if (method_exists($user, 'permissions')) {
            return collect($user->permissions)->map(fn($permission) => PermissionVO::fromArray([
                'id' => (string) $permission->id,
                'name' => $permission->name,
                'description' => $permission->description ?? null,
                'created_at' => $permission->created_at,
                'updated_at' => $permission->updated_at,
            ]))->all();
        }

        return [];
    }

    /**
     * Get users by status
     *
     * @return UserInterface[]
     */
    public function findByStatus(string $status): array
    {
        return UserModel::where('status', $status)
            ->get()
            ->map(fn($user) => $this->mapToDomainObject($user))
            ->all();
    }

    /**
     * Get users by role
     *
     * @return UserInterface[]
     */
    public function findByRole(string $roleId): array
    {
        // This requires a relationship with roles table
        // Implementation depends on your role system (e.g., Spatie permissions)
        $users = UserModel::whereHas('roles', function ($query) use ($roleId) {
            $query->where('roles.id', $roleId)
                  ->orWhere('roles.name', $roleId);
        })->get();

        return $users->map(fn($user) => $this->mapToDomainObject($user))->all();
    }

    /**
     * Search users by query
     *
     * @param array<string, mixed> $criteria Search criteria
     * @return UserInterface[]
     */
    public function search(array $criteria): array
    {
        $query = UserModel::query();

        // Apply search criteria
        if (isset($criteria['name'])) {
            $query->where('name', 'like', '%' . $criteria['name'] . '%');
        }

        if (isset($criteria['email'])) {
            $query->where('email', 'like', '%' . $criteria['email'] . '%');
        }

        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (isset($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        // Apply limit if provided
        if (isset($criteria['limit'])) {
            $query->limit((int) $criteria['limit']);
        }

        return $query->get()->map(fn($user) => $this->mapToDomainObject($user))->all();
    }

    // ========================================================================
    // UserPersistInterface Methods (Write Operations)
    // ========================================================================

    /**
     * Create a new user
     *
     * @param array<string, mixed> $data User data
     */
    public function create(array $data): UserInterface
    {
        $eloquentUser = UserModel::create([
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'password' => $data['password_hash'] ?? $data['password'],
            'status' => $data['status'] ?? 'active',
            'tenant_id' => $data['tenant_id'] ?? null,
        ]);

        // Handle roles if provided
        if (isset($data['roles']) && method_exists($eloquentUser, 'syncRoles')) {
            $eloquentUser->syncRoles($data['roles']);
        }

        return $this->mapToDomainObject($eloquentUser->fresh());
    }

    /**
     * Update an existing user
     *
     * @param string $id User identifier
     * @param array<string, mixed> $data Updated user data
     */
    public function update(string $id, array $data): UserInterface
    {
        $user = UserModel::findOrFail($id);

        // Build update array excluding null values
        $updateData = [];
        
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }
        if (isset($data['password_hash'])) {
            $updateData['password'] = $data['password_hash'];
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        if (isset($data['tenant_id'])) {
            $updateData['tenant_id'] = $data['tenant_id'];
        }

        $user->update($updateData);

        return $this->mapToDomainObject($user->fresh());
    }

    /**
     * Delete a user
     */
    public function delete(string $id): bool
    {
        $user = UserModel::find($id);

        if (!$user) {
            return false;
        }

        return (bool) $user->delete();
    }

    /**
     * Assign a role to a user
     */
    public function assignRole(string $userId, string $roleId): void
    {
        $user = UserModel::findOrFail($userId);

        if (method_exists($user, 'assignRole')) {
            $user->assignRole($roleId);
        } elseif (method_exists($user, 'roles')) {
            $user->roles()->attach($roleId);
        }
    }

    /**
     * Revoke a role from a user
     */
    public function revokeRole(string $userId, string $roleId): void
    {
        $user = UserModel::findOrFail($userId);

        if (method_exists($user, 'removeRole')) {
            $user->removeRole($roleId);
        } elseif (method_exists($user, 'roles')) {
            $user->roles()->detach($roleId);
        }
    }

    /**
     * Assign a permission directly to a user
     */
    public function assignPermission(string $userId, string $permissionId): void
    {
        $user = UserModel::findOrFail($userId);

        if (method_exists($user, 'givePermissionTo')) {
            $user->givePermissionTo($permissionId);
        } elseif (method_exists($user, 'permissions')) {
            $user->permissions()->attach($permissionId);
        }
    }

    /**
     * Revoke a permission from a user
     */
    public function revokePermission(string $userId, string $permissionId): void
    {
        $user = UserModel::findOrFail($userId);

        if (method_exists($user, 'revokePermissionTo')) {
            $user->revokePermissionTo($permissionId);
        } elseif (method_exists($user, 'permissions')) {
            $user->permissions()->detach($permissionId);
        }
    }

    /**
     * Update user's last login timestamp
     */
    public function updateLastLogin(string $userId): void
    {
        UserModel::where('id', $userId)->update([
            'last_login_at' => now(),
        ]);
    }

    /**
     * Increment failed login attempts
     */
    public function incrementFailedLoginAttempts(string $userId): int
    {
        $user = UserModel::findOrFail($userId);

        $attempts = ($user->failed_login_attempts ?? 0) + 1;

        $user->update(['failed_login_attempts' => $attempts]);

        return $attempts;
    }

    /**
     * Reset failed login attempts
     */
    public function resetFailedLoginAttempts(string $userId): void
    {
        UserModel::where('id', $userId)->update([
            'failed_login_attempts' => 0,
        ]);
    }

    /**
     * Lock a user account
     */
    public function lockAccount(string $userId, string $reason): void
    {
        UserModel::where('id', $userId)->update([
            'status' => 'locked',
            'lock_reason' => $reason,
            'locked_at' => now(),
        ]);
    }

    /**
     * Unlock a user account
     */
    public function unlockAccount(string $userId): void
    {
        UserModel::where('id', $userId)->update([
            'status' => 'active',
            'lock_reason' => null,
            'locked_at' => null,
            'failed_login_attempts' => 0,
        ]);
    }

    // ========================================================================
    // Private Helper Methods
    // ========================================================================

    /**
     * Converts the Eloquent Model into the Domain Value Object
     */
    private function mapToDomainObject(UserModel $userModel): UserInterface
    {
        return UserVO::fromEloquent($userModel);
    }
}