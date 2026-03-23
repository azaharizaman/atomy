<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Adapters;

use App\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Nexus\Laravel\Identity\Mappers\LaravelUserMapper;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserPersistInterface;
use Nexus\Identity\Exceptions\UserNotFoundException;

final readonly class UserPersistAdapter implements UserPersistInterface
{
    public function create(array $data): UserInterface
    {
        $tenantId = (string) ($data['tenant_id'] ?? '');
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $passwordHash = (string) ($data['password_hash'] ?? '');
        $status = (string) ($data['status'] ?? 'active');
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = isset($data['last_name']) ? trim((string) $data['last_name']) : '';
        $name = $this->composeDisplayName($firstName, $lastName, $email);

        if ($tenantId === '' || $email === '' || $passwordHash === '') {
            throw new \InvalidArgumentException('tenant_id, email, and password_hash are required');
        }

        $user = UserModel::query()->create([
            'tenant_id' => $tenantId,
            'email' => $email,
            'name' => $name,
            'password_hash' => $passwordHash,
            'role' => (string) ($data['role'] ?? 'user'),
            'status' => $status,
            'timezone' => (string) ($data['timezone'] ?? 'UTC'),
            'locale' => (string) ($data['locale'] ?? 'en'),
            'email_verified_at' => $data['email_verified_at'] ?? null,
        ]);

        return LaravelUserMapper::fromModel($user);
    }

    public function update(string $id, array $data): UserInterface
    {
        $tenantId = $data['tenant_id'] ?? null;
        
        if ($tenantId !== null) {
            $user = UserModel::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($id)
                ->first();
        } else {
            $user = UserModel::query()->whereKey($id)->first();
        }
        
        if ($user === null) {
            throw new UserNotFoundException($id);
        }

        $payload = [];
        if (array_key_exists('email', $data)) {
            $payload['email'] = strtolower(trim((string) $data['email']));
        }
        if (array_key_exists('password_hash', $data)) {
            $payload['password_hash'] = (string) $data['password_hash'];
        }
        if (array_key_exists('status', $data)) {
            $payload['status'] = (string) $data['status'];
        }
        if (array_key_exists('tenant_id', $data)) {
            $payload['tenant_id'] = (string) $data['tenant_id'];
        }
        if (array_key_exists('timezone', $data)) {
            $payload['timezone'] = (string) $data['timezone'];
        }
        if (array_key_exists('locale', $data)) {
            $payload['locale'] = (string) $data['locale'];
        }
        if (array_key_exists('first_name', $data) || array_key_exists('last_name', $data)) {
            $first = array_key_exists('first_name', $data)
                ? trim((string) $data['first_name'])
                : (explode(' ', (string) $user->name, 2)[0] ?? '');
            $last = array_key_exists('last_name', $data) ? trim((string) $data['last_name']) : '';
            $payload['name'] = $this->composeDisplayName($first, $last, (string) $user->email);
        }
        if (array_key_exists('name', $data)) {
            $payload['name'] = trim((string) $data['name']);
        }
        if (array_key_exists('email_verified_at', $data)) {
            $payload['email_verified_at'] = $data['email_verified_at'];
        }

        if ($payload !== []) {
            $user->fill($payload);
            $user->save();
        }

        return LaravelUserMapper::fromModel($user->fresh() ?? $user);
    }

    public function delete(string $id, ?string $tenantId = null): bool
    {
        $query = UserModel::query()->whereKey($id);
        
        if ($tenantId !== null && $tenantId !== '') {
            $query->where('tenant_id', $tenantId);
        }
        
        return $query->delete() > 0;
    }

    public function assignRole(string $userId, string $roleId, ?string $tenantId = null): void
    {
        $query = UserModel::query()->whereKey($userId);
        
        if ($tenantId !== null && $tenantId !== '') {
            $query->where('tenant_id', $tenantId);
        }
        
        $user = $query->first();
        
        if ($user === null) {
            throw new UserNotFoundException($userId);
        }
        
        $user->role = $roleId;
        $user->save();
    }

    public function revokeRole(string $userId, string $roleId, ?string $tenantId = null): void
    {
        $query = UserModel::query()->whereKey($userId);
        
        if ($tenantId !== null && $tenantId !== '') {
            $query->where('tenant_id', $tenantId);
        }
        
        $user = $query->first();
        
        if ($user === null) {
            return;
        }
        
        if ((string) $user->role === $roleId) {
            $user->role = 'user';
            $user->save();
        }
    }

    public function assignPermission(string $userId, string $permissionId, ?string $tenantId = null): void
    {
        $user = $this->findUserScoped($userId, $tenantId);
        
        if ($user === null) {
            throw new UserNotFoundException($userId);
        }
        
        DB::table('user_permissions')->updateOrInsert(
            [
                'user_id' => $user->id,
                'permission_id' => $permissionId,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
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

    public function updateLastLogin(string $userId, ?string $tenantId = null): void
    {
        $query = UserModel::query()->whereKey($userId);
        
        if ($tenantId !== null && $tenantId !== '') {
            $query->where('tenant_id', $tenantId);
        }
        
        $updated = $query->update(['last_login_at' => now()]);
        
        if ($updated === 0) {
            throw new UserNotFoundException($userId);
        }
    }

    public function incrementFailedLoginAttempts(string $userId, ?string $tenantId = null): int
    {
        $query = UserModel::query()->whereKey($userId);
        
        if ($tenantId !== null && $tenantId !== '') {
            $query->where('tenant_id', $tenantId);
        }
        
        $user = $query->first();
        
        if ($user === null) {
            throw new UserNotFoundException($userId);
        }
        
        $user->failed_login_attempts = ($user->failed_login_attempts ?? 0) + 1;
        $user->save();
        
        return $user->failed_login_attempts;
    }

    public function resetFailedLoginAttempts(string $userId, ?string $tenantId = null): void
    {
        $query = UserModel::query()->whereKey($userId);
        
        if ($tenantId !== null && $tenantId !== '') {
            $query->where('tenant_id', $tenantId);
        }
        
        $updated = $query->update(['failed_login_attempts' => 0]);
        
        if ($updated === 0) {
            throw new UserNotFoundException($userId);
        }
    }

    public function lockAccount(string $userId, string $reason, ?string $tenantId = null): void
    {
        $query = UserModel::query()->whereKey($userId);
        
        if ($tenantId !== null && $tenantId !== '') {
            $query->where('tenant_id', $tenantId);
        }
        
        $updated = $query->update([
            'status' => 'locked',
            'lockout_reason' => $reason,
            'lockout_expires_at' => null,
        ]);
        
        if ($updated === 0) {
            throw new UserNotFoundException($userId);
        }
    }

    public function unlockAccount(string $userId, ?string $tenantId = null): void
    {
        $query = UserModel::query()->whereKey($userId);
        
        if ($tenantId !== null && $tenantId !== '') {
            $query->where('tenant_id', $tenantId);
        }
        
        $updated = $query->update([
            'status' => 'active',
            'lockout_reason' => null,
            'lockout_expires_at' => null,
            'failed_login_attempts' => 0,
        ]);
        
        if ($updated === 0) {
            throw new UserNotFoundException($userId);
        }
    }

    private function findUserScoped(string $userId, ?string $tenantId): ?UserModel
    {
        $query = UserModel::query()->whereKey($userId);
        
        if ($tenantId !== null && $tenantId !== '') {
            $query->where('tenant_id', $tenantId);
        }
        
        return $query->first();
    }

    private function composeDisplayName(string $firstName, string $lastName, string $fallbackEmail): string
    {
        $parts = array_filter([$firstName, $lastName], static fn (string $p): bool => $p !== '');
        if ($parts === []) {
            return $fallbackEmail;
        }

        return implode(' ', $parts);
    }
}
