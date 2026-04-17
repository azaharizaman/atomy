<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\User as UserModel;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserPersistInterface;
use Nexus\Identity\Exceptions\UserNotFoundException;

/**
 * Writes against Atomy-Q `users` table (JIT SSO provisioning, profile updates).
 */
final readonly class AtomyUserPersist implements UserPersistInterface
{
    public function create(array $data): UserInterface
    {
        $tenantId = (string) ($data['tenant_id'] ?? '');
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $passwordHash = (string) ($data['password_hash'] ?? '');
        $status = (string) ($data['status'] ?? 'active');
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = isset($data['last_name']) ? trim((string) $data['last_name']) : '';
        $explicitName = trim((string) ($data['name'] ?? ''));
        $name = $explicitName !== ''
            ? $explicitName
            : $this->composeDisplayName($firstName, $lastName, $email);

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
            'email_verified_at' => $data['email_verified_at'] ?? now(),
        ]);

        return new AtomyIdentityUser($user);
    }

    public function update(string $id, array $data): UserInterface
    {
        $user = UserModel::query()->whereKey($id)->first();
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

        return new AtomyIdentityUser($user->fresh() ?? $user);
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
        $user = UserModel::query()->whereKey($userId)->first();
        if ($user === null) {
            throw new UserNotFoundException($userId);
        }
        if ($tenantId !== null && $tenantId !== '' && (string) $user->tenant_id !== $tenantId) {
            throw new UserNotFoundException($userId);
        }
        $user->role = $roleId;
        $user->save();
    }

    public function revokeRole(string $userId, string $roleId, ?string $tenantId = null): void
    {
        $user = UserModel::query()->whereKey($userId)->first();
        if ($user === null) {
            return;
        }
        if ($tenantId !== null && $tenantId !== '' && (string) $user->tenant_id !== $tenantId) {
            return;
        }
        if ((string) $user->role === $roleId) {
            $user->role = 'user';
            $user->save();
        }
    }

    public function assignPermission(string $userId, string $permissionId, ?string $tenantId = null): void
    {
        // No direct-permission storage in Atomy-Q schema.
    }

    public function revokePermission(string $userId, string $permissionId, ?string $tenantId = null): void
    {
    }

    public function updateLastLogin(string $userId): void
    {
        UserModel::query()->whereKey($userId)->update(['last_login_at' => now()]);
    }

    public function incrementFailedLoginAttempts(string $userId): int
    {
        $updated = UserModel::query()->whereKey($userId)->increment('failed_login_attempts');
        if ($updated === 0) {
            throw new UserNotFoundException($userId);
        }

        $attempts = UserModel::query()->whereKey($userId)->value('failed_login_attempts');

        return (int) ($attempts ?? 0);
    }

    public function resetFailedLoginAttempts(string $userId): void
    {
        if (! UserModel::query()->whereKey($userId)->exists()) {
            throw new UserNotFoundException($userId);
        }

        UserModel::query()->whereKey($userId)->update(['failed_login_attempts' => 0]);
    }

    public function lockAccount(string $userId, string $reason): void
    {
        if (! UserModel::query()->whereKey($userId)->exists()) {
            throw new UserNotFoundException($userId);
        }

        UserModel::query()->whereKey($userId)->update([
            'status' => 'locked',
            'lockout_reason' => trim($reason) !== '' ? $reason : null,
            'lockout_expires_at' => null,
        ]);
    }

    public function unlockAccount(string $userId): void
    {
        if (! UserModel::query()->whereKey($userId)->exists()) {
            throw new UserNotFoundException($userId);
        }

        UserModel::query()->whereKey($userId)->update([
            'status' => 'active',
            'lockout_reason' => null,
            'lockout_expires_at' => null,
            'failed_login_attempts' => 0,
        ]);
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
