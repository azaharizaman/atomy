<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Repositories;

use App\Models\Permission as PermissionModel;
use App\Models\Role as RoleModel;
use App\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Nexus\Identity\Contracts\PermissionInterface;
use Nexus\Identity\Contracts\RoleInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Identity\Exceptions\UserNotFoundException;

final readonly class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(string $id): UserInterface
    {
        return $this->mapUser($this->findUserOrFail($id));
    }

    public function findByEmail(string $email): UserInterface
    {
        $user = UserModel::query()
            ->where('email', $this->normalizeEmail($email))
            ->first();

        if ($user === null) {
            throw new UserNotFoundException($email);
        }

        return $this->mapUser($user);
    }

    public function findByEmailOrNull(string $email): ?UserInterface
    {
        $user = UserModel::query()
            ->where('email', $this->normalizeEmail($email))
            ->first();

        return $user === null ? null : $this->mapUser($user);
    }

    public function emailExists(string $email, ?string $excludeUserId = null): bool
    {
        $query = UserModel::query()->where('email', $this->normalizeEmail($email));

        if ($excludeUserId !== null && trim($excludeUserId) !== '') {
            $query->whereKeyNot($excludeUserId);
        }

        return $query->exists();
    }

    /**
     * @return RoleInterface[]
     */
    public function getUserRoles(string $userId, ?string $tenantId = null): array
    {
        $user = $this->findUserScoped($userId, $tenantId);
        if ($user === null) {
            return [];
        }

        $roleIds = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->pluck('role_id')
            ->all();

        $legacyRole = trim((string) ($user->role ?? ''));
        if ($legacyRole !== '' && $legacyRole !== 'user') {
            $roleIds[] = $legacyRole;
        }

        $roleIds = array_values(array_unique(array_filter($roleIds, static fn (mixed $value): bool => is_string($value) && trim($value) !== '')));
        if ($roleIds === []) {
            return [];
        }

        $roles = RoleModel::query()
            ->whereIn('id', $roleIds)
            ->orderBy('name')
            ->get();

        return $roles
            ->filter(function (RoleModel $role) use ($tenantId): bool {
                $roleTenantId = $role->getTenantId();

                return $tenantId === null || $roleTenantId === null || $roleTenantId === $tenantId;
            })
            ->values()
            ->all();
    }

    /**
     * @return PermissionInterface[]
     */
    public function getUserPermissions(string $userId, ?string $tenantId = null): array
    {
        $user = $this->findUserScoped($userId, $tenantId);
        if ($user === null) {
            return [];
        }

        $permissionIds = [];

        foreach (
            DB::table('user_permissions')
                ->where('user_id', $user->id)
                ->pluck('permission_id')
                ->all() as $permissionId
        ) {
            if (is_string($permissionId) && trim($permissionId) !== '') {
                $permissionIds[] = $permissionId;
            }
        }

        $roleIds = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->pluck('role_id')
            ->all();

        $legacyRole = trim((string) ($user->role ?? ''));
        if ($legacyRole !== '' && $legacyRole !== 'user') {
            $roleIds[] = $legacyRole;
        }

        $roleIds = array_values(array_unique(array_filter($roleIds, static fn (mixed $value): bool => is_string($value) && trim($value) !== '')));

        if ($roleIds !== []) {
            foreach (
                DB::table('role_permissions')
                    ->whereIn('role_id', $roleIds)
                    ->pluck('permission_id')
                    ->all() as $permissionId
            ) {
                if (is_string($permissionId) && trim($permissionId) !== '') {
                    $permissionIds[] = $permissionId;
                }
            }
        }

        $permissionIds = array_values(array_unique($permissionIds));
        if ($permissionIds === []) {
            return [];
        }

        return PermissionModel::query()
            ->whereIn('id', $permissionIds)
            ->orderBy('name')
            ->get()
            ->all();
    }

    /**
     * @return UserInterface[]
     */
    public function findByStatus(string $status, string $tenantId): array
    {
        return UserModel::query()
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->orderBy('email')
            ->get()
            ->map(fn (UserModel $user): UserInterface => $this->mapUser($user))
            ->all();
    }

    /**
     * @return UserInterface[]
     */
    public function findByRole(string $roleId, string $tenantId): array
    {
        $legacyUsers = UserModel::query()
            ->where('tenant_id', $tenantId)
            ->where('role', $roleId)
            ->pluck('id')
            ->all();

        $pivotUsers = DB::table('user_roles')
            ->join('users', 'users.id', '=', 'user_roles.user_id')
            ->where('users.tenant_id', $tenantId)
            ->where('user_roles.role_id', $roleId)
            ->pluck('users.id')
            ->all();

        $userIds = array_values(array_unique(array_merge($legacyUsers, $pivotUsers)));
        if ($userIds === []) {
            return [];
        }

        return UserModel::query()
            ->whereIn('id', $userIds)
            ->orderBy('email')
            ->get()
            ->map(fn (UserModel $user): UserInterface => $this->mapUser($user))
            ->all();
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return UserInterface[]
     */
    public function search(array $criteria): array
    {
        $query = UserModel::query();

        if (isset($criteria['tenant_id']) && is_string($criteria['tenant_id']) && trim($criteria['tenant_id']) !== '') {
            $query->where('tenant_id', trim($criteria['tenant_id']));
        }

        if (isset($criteria['status']) && is_string($criteria['status']) && trim($criteria['status']) !== '') {
            $query->where('status', trim($criteria['status']));
        }

        if (isset($criteria['email']) && is_string($criteria['email']) && trim($criteria['email']) !== '') {
            $query->where('email', 'like', '%' . $this->normalizeEmail($criteria['email']) . '%');
        }

        if (isset($criteria['name']) && is_string($criteria['name']) && trim($criteria['name']) !== '') {
            $query->where('name', 'like', '%' . trim($criteria['name']) . '%');
        }

        if (isset($criteria['role']) && is_string($criteria['role']) && trim($criteria['role']) !== '') {
            $role = trim($criteria['role']);
            $query->where(function ($builder) use ($role): void {
                $builder->where('role', $role)
                    ->orWhereExists(function ($subquery) use ($role): void {
                        $subquery->selectRaw('1')
                            ->from('user_roles')
                            ->whereColumn('user_roles.user_id', 'users.id')
                            ->where('user_roles.role_id', $role);
                    });
            });
        }

        return $query
            ->orderBy('email')
            ->limit(100)
            ->get()
            ->map(fn (UserModel $user): UserInterface => $this->mapUser($user))
            ->all();
    }

    public function create(array $data): UserInterface
    {
        $payload = $this->normalizeUserPayload($data);
        $user = UserModel::query()->create($payload);

        return $this->mapUser($user);
    }

    public function update(string $id, array $data): UserInterface
    {
        $user = $this->findUserOrFail($id);
        $payload = $this->normalizeUserPayload($data, $user);

        if ($payload !== []) {
            $user->fill($payload);
            $user->save();
        }

        return $this->mapUser($user->fresh() ?? $user);
    }

    public function delete(string $id, ?string $tenantId = null): bool
    {
        $query = UserModel::query()->whereKey($id);

        if ($tenantId !== null && trim($tenantId) !== '') {
            $query->where('tenant_id', $tenantId);
        }

        return $query->delete() > 0;
    }

    public function assignRole(string $userId, string $roleId, ?string $tenantId = null): void
    {
        $user = $this->findUserScopedOrFail($userId, $tenantId);
        $role = $this->findRoleOrNull($roleId, $tenantId ?? $user->tenant_id);

        DB::table('user_roles')->updateOrInsert(
            [
                'user_id' => $user->id,
                'role_id' => $role?->id ?? $roleId,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $user->role = $role?->id ?? $roleId;
        $user->save();
    }

    public function revokeRole(string $userId, string $roleId, ?string $tenantId = null): void
    {
        $user = $this->findUserScoped($userId, $tenantId);
        if ($user === null) {
            return;
        }

        DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $roleId)
            ->delete();

        if ((string) $user->role === $roleId) {
            $user->role = 'user';
            $user->save();
        }
    }

    public function assignPermission(string $userId, string $permissionId, ?string $tenantId = null): void
    {
        $user = $this->findUserScopedOrFail($userId, $tenantId);
        $permission = PermissionModel::query()->whereKey($permissionId)->first();
        $resolvedPermissionId = $permission?->id ?? $permissionId;

        DB::table('user_permissions')->updateOrInsert(
            [
                'user_id' => $user->id,
                'permission_id' => $resolvedPermissionId,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function revokePermission(string $userId, string $permissionId, ?string $tenantId = null): void
    {
        $user = $this->findUserScoped($userId, $tenantId);
        if ($user === null) {
            return;
        }

        DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->where('permission_id', $permissionId)
            ->delete();
    }

    public function updateLastLogin(string $userId): void
    {
        UserModel::query()->whereKey($userId)->update(['last_login_at' => now()]);
    }

    public function incrementFailedLoginAttempts(string $userId): int
    {
        $user = $this->findUserOrFail($userId);
        $user->failed_login_attempts = (int) ($user->failed_login_attempts ?? 0) + 1;
        $user->save();

        return (int) $user->failed_login_attempts;
    }

    public function resetFailedLoginAttempts(string $userId): void
    {
        UserModel::query()->whereKey($userId)->update(['failed_login_attempts' => 0]);
    }

    public function lockAccount(string $userId, string $reason): void
    {
        UserModel::query()->whereKey($userId)->update([
            'status' => 'locked',
            'lockout_reason' => trim($reason) !== '' ? $reason : null,
            'lockout_expires_at' => null,
        ]);
    }

    public function unlockAccount(string $userId): void
    {
        UserModel::query()->whereKey($userId)->update([
            'status' => 'active',
            'lockout_reason' => null,
            'lockout_expires_at' => null,
        ]);
    }

    private function findUserOrFail(string $id): UserModel
    {
        $user = UserModel::query()->whereKey($id)->first();
        if ($user === null) {
            throw new UserNotFoundException($id);
        }

        return $user;
    }

    private function findUserScopedOrFail(string $id, ?string $tenantId): UserModel
    {
        $user = $this->findUserScoped($id, $tenantId);
        if ($user === null) {
            throw new UserNotFoundException($id);
        }

        return $user;
    }

    private function findUserScoped(string $id, ?string $tenantId): ?UserModel
    {
        $query = UserModel::query()->whereKey($id);
        if ($tenantId !== null && trim($tenantId) !== '') {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }

    private function findRoleOrNull(string $roleId, ?string $tenantId): ?RoleModel
    {
        $query = RoleModel::query()->whereKey($roleId);
        if ($tenantId !== null && trim($tenantId) !== '') {
            $query->where(function ($builder) use ($tenantId): void {
                $builder->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            });
        }

        return $query->first();
    }

    private function mapUser(UserModel $user): UserInterface
    {
        return $user;
    }

    /**
     * @param array<string, mixed> $data
     * @param UserModel|null $existingUser
     *
     * @return array<string, mixed>
     */
    private function normalizeUserPayload(array $data, ?UserModel $existingUser = null): array
    {
        $payload = [];

        if (array_key_exists('tenant_id', $data)) {
            $tenantId = trim((string) $data['tenant_id']);
            if ($tenantId !== '') {
                $payload['tenant_id'] = $tenantId;
            }
        } elseif ($existingUser !== null && isset($existingUser->tenant_id)) {
            $payload['tenant_id'] = (string) $existingUser->tenant_id;
        }

        if (array_key_exists('email', $data)) {
            $payload['email'] = $this->normalizeEmail((string) $data['email']);
        }

        if (array_key_exists('password_hash', $data)) {
            $payload['password_hash'] = (string) $data['password_hash'];
        }

        if (array_key_exists('name', $data)) {
            $payload['name'] = trim((string) $data['name']);
        } elseif (array_key_exists('first_name', $data) || array_key_exists('last_name', $data)) {
            $firstName = array_key_exists('first_name', $data) ? trim((string) $data['first_name']) : (string) ($existingUser?->name ?? '');
            $lastName = array_key_exists('last_name', $data) ? trim((string) $data['last_name']) : '';
            $payload['name'] = $this->composeDisplayName($firstName, $lastName, (string) ($existingUser?->email ?? ''));
        }

        if (array_key_exists('role', $data)) {
            $payload['role'] = trim((string) $data['role']);
        }

        if (array_key_exists('status', $data)) {
            $payload['status'] = trim((string) $data['status']);
        }

        if (array_key_exists('timezone', $data)) {
            $payload['timezone'] = trim((string) $data['timezone']);
        }

        if (array_key_exists('locale', $data)) {
            $payload['locale'] = trim((string) $data['locale']);
        }

        if (array_key_exists('email_verified_at', $data)) {
            $payload['email_verified_at'] = $data['email_verified_at'];
        }

        if (array_key_exists('metadata', $data)) {
            $payload['metadata'] = is_array($data['metadata']) ? $data['metadata'] : [];
        }

        if (array_key_exists('failed_login_attempts', $data)) {
            $payload['failed_login_attempts'] = (int) $data['failed_login_attempts'];
        }

        if (array_key_exists('lockout_reason', $data)) {
            $reason = trim((string) $data['lockout_reason']);
            $payload['lockout_reason'] = $reason === '' ? null : $reason;
        }

        if (array_key_exists('lockout_expires_at', $data)) {
            $payload['lockout_expires_at'] = $data['lockout_expires_at'];
        }

        if (array_key_exists('mfa_enabled', $data)) {
            $payload['mfa_enabled'] = (bool) $data['mfa_enabled'];
        }

        return $payload;
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function composeDisplayName(string $firstName, string $lastName, string $fallbackEmail): string
    {
        $parts = array_filter([$firstName, $lastName], static fn (string $value): bool => trim($value) !== '');
        if ($parts === []) {
            return $fallbackEmail;
        }

        return trim(implode(' ', $parts));
    }
}
