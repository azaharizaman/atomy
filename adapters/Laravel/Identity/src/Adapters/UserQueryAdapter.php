<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Adapters;

use App\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Nexus\Laravel\Identity\Mappers\LaravelUserMapper;
use Nexus\Laravel\Identity\ValueObjects\UserPermissionDTO;
use Nexus\Laravel\Identity\ValueObjects\UserRoleDTO;
use Nexus\Identity\Contracts\PermissionInterface;
use Nexus\Identity\Contracts\RoleInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\Identity\Exceptions\UserNotFoundException;
use Nexus\Identity\ValueObjects\RoleEnum;

final readonly class UserQueryAdapter implements UserQueryInterface
{
    public function findById(string $id, ?string $tenantId = null): UserInterface
    {
        $query = UserModel::query()->whereKey($id);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $user = $query->first();

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

    public function findByEmail(string $email, ?string $tenantId = null): UserInterface
    {
        $normalizedEmail = strtolower(trim($email));

        $query = UserModel::query()->where('email', $normalizedEmail);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $user = $query->first();

        if ($user === null) {
            throw new UserNotFoundException("email:{$email}");
        }

        return LaravelUserMapper::fromModel($user);
    }

    public function findByEmailOrNull(string $email, ?string $tenantId = null): ?UserInterface
    {
        $normalizedEmail = strtolower(trim($email));

        $query = UserModel::query()->where('email', $normalizedEmail);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $user = $query->first();

        if ($user === null) {
            return null;
        }

        return LaravelUserMapper::fromModel($user);
    }

    public function emailExists(string $email, ?string $excludeUserId = null, ?string $tenantId = null): bool
    {
        $normalizedEmail = strtolower(trim($email));

        $query = UserModel::query()->where('email', $normalizedEmail);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        if ($excludeUserId !== null) {
            $query->whereKeyNot($excludeUserId);
        }

        return $query->exists();
    }

    public function getUserRoles(string $userId, string $tenantId): array
    {
        $user = UserModel::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($userId)
            ->first();

        if ($user === null) {
            return [];
        }

        $roleId = (string) $user->role;

        if ($roleId === '' || $roleId === RoleEnum::USER->value) {
            return [];
        }

        return [
            new UserRoleDTO(
                id: $roleId,
                tenantId: $tenantId,
                createdAt: $user->created_at?->toImmutable(),
                updatedAt: $user->updated_at?->toImmutable(),
            ),
        ];
    }

    public function getUserPermissions(string $userId, string $tenantId): array
    {
        $user = UserModel::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($userId)
            ->first();

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
            $permissions[] = UserPermissionDTO::fromDatabaseRow($record);
        }

        return $permissions;
    }

    public function findByStatus(string $status, string $tenantId): array
    {
        $users = UserModel::query()
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();

        return $users->map(
            static fn (UserModel $user): UserInterface => LaravelUserMapper::fromModel($user)
        )->all();
    }

    public function findByRole(string $roleId, string $tenantId): array
    {
        $users = UserModel::query()
            ->where('tenant_id', $tenantId)
            ->where('role', $roleId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $users->map(
            static fn (UserModel $user): UserInterface => LaravelUserMapper::fromModel($user)
        )->all();
    }

    public function search(array $criteria): array
    {
        $tenantId = $criteria['tenant_id'] ?? null;

        $query = UserModel::query();

        if (isset($criteria['tenant_id'])) {
            $tenantId = (string) $criteria['tenant_id'];
            if ($tenantId === '') {
                throw new \InvalidArgumentException('tenant_id must be a non-empty string when provided');
            }
            $query->where('tenant_id', $tenantId);
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
