<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Adapters;

use App\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Nexus\Laravel\Identity\Mappers\LaravelUserMapper;
use Nexus\Identity\Contracts\PermissionInterface;
use Nexus\Identity\Contracts\RoleInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\Identity\Exceptions\UserNotFoundException;

final readonly class UserQueryAdapter implements UserQueryInterface
{
    public function findById(string $id): UserInterface
    {
        $user = UserModel::query()->find($id);

        if ($user === null) {
            throw new UserNotFoundException($id);
        }

        return LaravelUserMapper::fromModel($user);
    }

    public function findByIdScoped(string $id, string $tenantId): UserInterface
    {
        $user = UserModel::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($id)
            ->first();

        if ($user === null) {
            throw new UserNotFoundException($id);
        }

        return LaravelUserMapper::fromModel($user);
    }

    public function findByEmail(string $email): UserInterface
    {
        $normalizedEmail = strtolower(trim($email));

        $user = UserModel::query()
            ->where('email', $normalizedEmail)
            ->first();

        if ($user === null) {
            throw new UserNotFoundException("email:{$email}");
        }

        return LaravelUserMapper::fromModel($user);
    }

    public function findByEmailOrNull(string $email): ?UserInterface
    {
        $normalizedEmail = strtolower(trim($email));

        $user = UserModel::query()
            ->where('email', $normalizedEmail)
            ->first();

        if ($user === null) {
            return null;
        }

        return LaravelUserMapper::fromModel($user);
    }

    public function emailExists(string $email, ?string $excludeUserId = null): bool
    {
        $normalizedEmail = strtolower(trim($email));

        $query = UserModel::query()->where('email', $normalizedEmail);

        if ($excludeUserId !== null) {
            $query->whereKeyNot($excludeUserId);
        }

        return $query->exists();
    }

    public function getUserRoles(string $userId): array
    {
        $user = UserModel::query()->find($userId);

        if ($user === null) {
            return [];
        }

        $roleId = (string) $user->role;

        if ($roleId === '' || $roleId === 'user') {
            return [];
        }

        return [
            new class($roleId) implements RoleInterface {
                public function __construct(private string $roleId)
                {
                }

                public function getId(): string
                {
                    return $this->roleId;
                }

                public function getName(): string
                {
                    return $this->roleId;
                }

                public function getDescription(): ?string
                {
                    return null;
                }

                public function getTenantId(): ?string
                {
                    return null;
                }

                public function isSystemRole(): bool
                {
                    return in_array($this->roleId, ['admin', 'super-admin'], true);
                }

                public function isSuperAdmin(): bool
                {
                    return $this->roleId === 'super-admin';
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
                    return $this->roleId === 'super-admin';
                }
            }
        ];
    }

    public function getUserPermissions(string $userId): array
    {
        $user = UserModel::query()->find($userId);

        if ($user === null) {
            return [];
        }

        $permissionRecords = DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->get();

        if ($permissionRecords->isEmpty()) {
            return [];
        }

        $permissions = [];
        foreach ($permissionRecords as $record) {
            $permissions[] = new class((string) $record->permission_id) implements PermissionInterface {
                public function __construct(private string $permissionId)
                {
                }

                public function getId(): string
                {
                    return $this->permissionId;
                }

                public function getName(): string
                {
                    return $this->permissionId;
                }

                public function getResource(): string
                {
                    $pos = strpos($this->permissionId, '.');

                    return $pos !== false ? substr($this->permissionId, 0, $pos) : $this->permissionId;
                }

                public function getAction(): string
                {
                    $pos = strpos($this->permissionId, '.');

                    return $pos !== false ? substr($this->permissionId, $pos + 1) : '*';
                }

                public function getDescription(): ?string
                {
                    return null;
                }

                public function isWildcard(): bool
                {
                    $parts = explode('.', $this->permissionId, 2);

                    return isset($parts[1]) && $parts[1] === '*';
                }

                public function getCreatedAt(): \DateTimeInterface
                {
                    return new \DateTimeImmutable();
                }

                public function getUpdatedAt(): \DateTimeInterface
                {
                    return new \DateTimeImmutable();
                }

                public function matches(string $permissionName): bool
                {
                    if ($this->permissionId === $permissionName) {
                        return true;
                    }

                    if ($this->isWildcard()) {
                        $targetParts = explode('.', $permissionName, 2);

                        return count($targetParts) === 2 && $targetParts[0] === $this->getResource();
                    }

                    return false;
                }
            };
        }

        return $permissions;
    }

    public function findByStatus(string $status): array
    {
        $users = UserModel::query()
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();

        return $users->map(
            static fn (UserModel $user): UserInterface => LaravelUserMapper::fromModel($user)
        )->all();
    }

    public function findByRole(string $roleId): array
    {
        $users = UserModel::query()
            ->where('role', $roleId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $users->map(
            static fn (UserModel $user): UserInterface => LaravelUserMapper::fromModel($user)
        )->all();
    }

    public function search(array $criteria): array
    {
        $query = UserModel::query();

        if (isset($criteria['tenant_id']) && $criteria['tenant_id'] !== '') {
            $query->where('tenant_id', (string) $criteria['tenant_id']);
        }

        if (isset($criteria['status']) && $criteria['status'] !== '') {
            $query->where('status', (string) $criteria['status']);
        }

        if (isset($criteria['role']) && $criteria['role'] !== '') {
            $query->where('role', (string) $criteria['role']);
        }

        if (isset($criteria['email']) && $criteria['email'] !== '') {
            $query->where('email', 'like', '%' . strtolower(trim((string) $criteria['email'])) . '%');
        }

        if (isset($criteria['name']) && $criteria['name'] !== '') {
            $query->where('name', 'like', '%' . trim((string) $criteria['name']) . '%');
        }

        if (isset($criteria['email_verified']) && $criteria['email_verified'] !== '') {
            $isVerified = filter_var($criteria['email_verified'], FILTER_VALIDATE_BOOLEAN);
            if ($isVerified) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        if (isset($criteria['limit']) && is_numeric($criteria['limit'])) {
            $query->limit((int) $criteria['limit']);
        }

        if (isset($criteria['offset']) && is_numeric($criteria['offset'])) {
            $query->offset((int) $criteria['offset']);
        }

        if (isset($criteria['order_by']) && isset($criteria['order_dir'])) {
            $orderBy = (string) $criteria['order_by'];
            $orderDir = strtolower((string) $criteria['order_dir']) === 'asc' ? 'asc' : 'desc';

            $allowedColumns = ['created_at', 'updated_at', 'email', 'name', 'status'];
            if (in_array($orderBy, $allowedColumns, true)) {
                $query->orderBy($orderBy, $orderDir);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $users = $query->get();

        return $users->map(
            static fn (UserModel $user): UserInterface => LaravelUserMapper::fromModel($user)
        )->all();
    }
}
