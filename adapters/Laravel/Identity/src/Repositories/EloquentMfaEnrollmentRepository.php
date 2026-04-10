<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Repositories;

use App\Models\MfaEnrollment as MfaEnrollmentModel;
use Nexus\Identity\Contracts\MfaEnrollmentDataInterface;
use Nexus\Identity\Contracts\MfaEnrollmentInterface;
use Nexus\Identity\Contracts\MfaEnrollmentRepositoryInterface;
use Nexus\Identity\ValueObjects\MfaMethod;
use Nexus\Laravel\Identity\Mappers\LaravelMfaEnrollmentMapper;

final class EloquentMfaEnrollmentRepository implements MfaEnrollmentRepositoryInterface
{
    public function findById(string $enrollmentId): ?MfaEnrollmentDataInterface
    {
        $model = MfaEnrollmentModel::query()->find($enrollmentId);
        return $model ? LaravelMfaEnrollmentMapper::fromModel($model) : null;
    }

    public function findByUserId(string $userId): array
    {
        return MfaEnrollmentModel::query()
            ->where('user_id', $userId)
            ->get()
            ->map(fn (MfaEnrollmentModel $m) => LaravelMfaEnrollmentMapper::fromModel($m))
            ->all();
    }

    public function findActiveByUserId(string $userId): array
    {
        return MfaEnrollmentModel::query()
            ->where('user_id', $userId)
            ->where('verified', true)
            ->where('revoked', false)
            ->get()
            ->map(fn (MfaEnrollmentModel $m) => LaravelMfaEnrollmentMapper::fromModel($m))
            ->all();
    }

    public function findByUserAndMethod(string $userId, MfaMethod $method): ?MfaEnrollmentDataInterface
    {
        $model = MfaEnrollmentModel::query()
            ->where('user_id', $userId)
            ->where('method', $method->value)
            ->first();
        return $model ? LaravelMfaEnrollmentMapper::fromModel($model) : null;
    }

    public function findPrimaryByUserId(string $userId): ?MfaEnrollmentDataInterface
    {
        $model = MfaEnrollmentModel::query()
            ->where('user_id', $userId)
            ->where('is_primary', true)
            ->first();
        return $model ? LaravelMfaEnrollmentMapper::fromModel($model) : null;
    }

    public function countActiveByUserId(string $userId): int
    {
        return MfaEnrollmentModel::query()
            ->where('user_id', $userId)
            ->where('verified', true)
            ->where('revoked', false)
            ->count();
    }

    public function hasVerifiedEnrollment(string $userId): bool
    {
        return MfaEnrollmentModel::query()
            ->where('user_id', $userId)
            ->where('verified', true)
            ->where('revoked', false)
            ->exists();
    }

    public function findUnverifiedOlderThan(int $hoursOld): array
    {
        return MfaEnrollmentModel::query()
            ->where('verified', false)
            ->where('created_at', '<', now()->subHours($hoursOld))
            ->get()
            ->map(fn (MfaEnrollmentModel $m) => LaravelMfaEnrollmentMapper::fromModel($m))
            ->all();
    }

    public function findPendingByUserAndMethod(string $userId, MfaMethod $method): ?MfaEnrollmentDataInterface
    {
        $model = MfaEnrollmentModel::query()
            ->where('user_id', $userId)
            ->where('method', $method->value)
            ->where('verified', false)
            ->first();
        return $model ? LaravelMfaEnrollmentMapper::fromModel($model) : null;
    }

    public function findActiveByUserAndMethod(string $userId, MfaMethod $method): ?MfaEnrollmentDataInterface
    {
        $model = MfaEnrollmentModel::query()
            ->where('user_id', $userId)
            ->where('method', $method->value)
            ->where('verified', true)
            ->where('revoked', false)
            ->first();
        return $model ? LaravelMfaEnrollmentMapper::fromModel($model) : null;
    }

    public function findActiveBackupCodes(string $userId): array
    {
        return MfaEnrollmentModel::query()
            ->where('user_id', $userId)
            ->where('method', MfaMethod::BACKUP_CODE->value)
            ->where('verified', true)
            ->where('revoked', false)
            ->get()
            ->map(fn (MfaEnrollmentModel $m) => LaravelMfaEnrollmentMapper::fromModel($m))
            ->all();
    }

    public function save(MfaEnrollmentInterface $enrollment): MfaEnrollmentInterface
    {
        $model = MfaEnrollmentModel::query()->updateOrCreate(['id' => $enrollment->getId()], [
            'user_id' => $enrollment->getUserId(),
            'method' => $enrollment->getMethod()->value,
            'verified' => $enrollment->isVerified(),
            'revoked' => $enrollment->isRevoked(),
            'is_primary' => $enrollment->isPrimary(),
            'last_used_at' => $enrollment->getLastUsedAt(),
        ]);
        return LaravelMfaEnrollmentMapper::fromModel($model);
    }

    public function delete(string $enrollmentId): bool
    {
        return (bool) MfaEnrollmentModel::query()->whereKey($enrollmentId)->delete();
    }

    public function setPrimary(string $enrollmentId): bool
    {
        $enrollment = MfaEnrollmentModel::query()->findOrFail($enrollmentId);
        MfaEnrollmentModel::query()->where('user_id', $enrollment->user_id)->update(['is_primary' => false]);
        return (bool) $enrollment->update(['is_primary' => true]);
    }

    public function activate(string $enrollmentId): bool
    {
        return (bool) MfaEnrollmentModel::query()->whereKey($enrollmentId)->update(['verified' => true]);
    }

    public function revoke(string $enrollmentId): bool
    {
        return (bool) MfaEnrollmentModel::query()->whereKey($enrollmentId)->update(['revoked' => true]);
    }

    public function revokeByUserAndMethod(string $userId, string $method): int
    {
        return MfaEnrollmentModel::query()
            ->where('user_id', $userId)
            ->where('method', $method)
            ->update(['revoked' => true]);
    }

    public function revokeAllByUserId(string $userId): int
    {
        return MfaEnrollmentModel::query()
            ->where('user_id', $userId)
            ->update(['revoked' => true]);
    }

    public function consumeBackupCode(string $enrollmentId, \DateTimeImmutable $consumedAt): bool
    {
        return (bool) MfaEnrollmentModel::query()->whereKey($enrollmentId)->update(['last_used_at' => $consumedAt]);
    }

    public function updateLastUsed(string $enrollmentId, \DateTimeImmutable $lastUsedAt): bool
    {
        return (bool) MfaEnrollmentModel::query()->whereKey($enrollmentId)->update(['last_used_at' => $lastUsedAt]);
    }
}
