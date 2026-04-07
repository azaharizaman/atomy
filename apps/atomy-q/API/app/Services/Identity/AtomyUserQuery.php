<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\Permission as PermissionModel;
use App\Models\Role as RoleModel;
use App\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
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

        $roleIds = array_values(array_unique(array_filter(
            $roleIds,
            static fn (mixed $value): bool => is_string($value) && trim($value) !== '',
        )));

        $roles = [];

        if ($roleIds !== []) {
            $roles = RoleModel::query()
                ->whereIn('id', $roleIds)
                ->orderBy('name')
                ->get()
                ->filter(static function (RoleModel $role) use ($tenantId): bool {
                    $roleTenantId = $role->getTenantId();

                    return $tenantId === null || $roleTenantId === null || $roleTenantId === $tenantId;
                })
                ->values()
                ->all();
        }

        // Legacy single-role column fallback (pre-RBAC).
        $legacyRole = trim((string) ($user->role ?? ''));
        if ($legacyRole !== '' && $legacyRole !== 'user') {
            $alreadyPresent = false;
            foreach ($roles as $role) {
                if ($role instanceof RoleInterface && strtolower(trim($role->getName())) === strtolower($legacyRole)) {
                    $alreadyPresent = true;
                    break;
                }
            }

            if (! $alreadyPresent) {
                $roles[] = new AtomyLegacyRole($legacyRole, $tenantId);
            }
        }

        return $roles;
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

        $refs = [];

        foreach (
            DB::table('user_permissions')
                ->where('user_id', $user->id)
                ->pluck('permission_id')
                ->all() as $permissionRef
        ) {
            if (is_string($permissionRef) && trim($permissionRef) !== '') {
                $refs[] = trim($permissionRef);
            }
        }

        $roleIds = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->pluck('role_id')
            ->all();

        $roleIds = array_values(array_unique(array_filter(
            $roleIds,
            static fn (mixed $value): bool => is_string($value) && trim($value) !== '',
        )));

        if ($roleIds !== []) {
            foreach (
                DB::table('role_permissions')
                    ->whereIn('role_id', $roleIds)
                    ->pluck('permission_id')
                    ->all() as $permissionRef
            ) {
                if (is_string($permissionRef) && trim($permissionRef) !== '') {
                    $refs[] = trim($permissionRef);
                }
            }
        }

        $refs = array_values(array_unique($refs));
        if ($refs === []) {
            return [];
        }

        // Some older paths may store permission "name" instead of ULID id; support both.
        return PermissionModel::query()
            ->whereIn('id', $refs)
            ->orWhereIn('name', $refs)
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
            ->get()
            ->map(fn (UserModel $u): UserInterface => new AtomyIdentityUser($u))
            ->all();
    }

    /**
     * @return UserInterface[]
     */
    public function findByRole(string $roleId, string $tenantId): array
    {
        return UserModel::query()
            ->where('tenant_id', $tenantId)
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

    private function findUserScoped(string $userId, ?string $tenantId): ?UserModel
    {
        $query = UserModel::query()->whereKey($userId);
        if ($tenantId !== null && trim($tenantId) !== '') {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }
}

/**
 * @internal
 */
final readonly class AtomyLegacyRole implements RoleInterface
{
    public function __construct(
        private string $name,
        private ?string $tenantId,
    ) {}

    public function getId(): string
    {
        // Stable pseudo-id so callers can include this in role lists without requiring a roles row.
        return 'legacy:' . strtolower(trim($this->name));
    }

    public function getName(): string
    {
        return trim($this->name);
    }

    public function getDescription(): ?string
    {
        return null;
    }

    public function getTenantId(): ?string
    {
        $tenantId = $this->tenantId;
        if ($tenantId === null) {
            return null;
        }

        $normalized = trim($tenantId);

        return $normalized === '' ? null : $normalized;
    }

    public function isSystemRole(): bool
    {
        return false;
    }

    public function isSuperAdmin(): bool
    {
        $normalized = strtolower(trim($this->name));

        return in_array($normalized, ['super-admin', 'super_admin', 'superadmin'], true);
    }

    public function getParentRoleId(): ?string
    {
        return null;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return new \DateTimeImmutable();
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return new \DateTimeImmutable();
    }

    public function requiresMfa(): bool
    {
        return $this->isSuperAdmin();
    }
}
