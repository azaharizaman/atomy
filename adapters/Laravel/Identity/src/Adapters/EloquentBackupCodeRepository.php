<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Adapters;

use App\Models\BackupCode as BackupCodeModel;
use Nexus\Identity\Contracts\BackupCodePersistInterface;
use Nexus\Identity\Contracts\BackupCodeQueryInterface;
use Nexus\Identity\Contracts\BackupCodeRepositoryInterface;
use Nexus\Identity\ValueObjects\BackupCode;
use Nexus\Identity\ValueObjects\BackupCodeSet;

final class EloquentBackupCodeRepository implements BackupCodeRepositoryInterface
{
    public function findByEnrollmentId(string $enrollmentId): BackupCodeSet
    {
        $models = BackupCodeModel::query()->where('enrollment_id', $enrollmentId)->get();
        $codes = [];
        foreach ($models as $model) {
            $codes[] = new BackupCode($model->code_hash, (bool) $model->consumed_at);
        }
        return new BackupCodeSet($codes);
    }

    public function findByHash(string $enrollmentId, string $hash): ?BackupCode
    {
        $model = BackupCodeModel::query()
            ->where('enrollment_id', $enrollmentId)
            ->where('code_hash', $hash)
            ->whereNull('consumed_at')
            ->first();
        return $model ? new BackupCode($model->code_hash, false) : null;
    }

    public function countRemaining(string $enrollmentId): int
    {
        return BackupCodeModel::query()
            ->where('enrollment_id', $enrollmentId)
            ->whereNull('consumed_at')
            ->count();
    }

    public function shouldTriggerRegeneration(string $enrollmentId, int $threshold = 2): bool
    {
        return $this->countRemaining($enrollmentId) <= $threshold;
    }

    public function saveSet(string $enrollmentId, BackupCodeSet $codeSet): bool
    {
        $this->deleteByEnrollmentId($enrollmentId);
        foreach ($codeSet->getCodes() as $code) {
            BackupCodeModel::query()->create([
                'enrollment_id' => $enrollmentId,
                'code_hash' => $code->getHash(),
                'consumed_at' => $code->isConsumed() ? now() : null,
            ]);
        }
        return true;
    }

    public function markAsConsumed(string $enrollmentId, string $codeHash): bool
    {
        return (bool) BackupCodeModel::query()
            ->where('enrollment_id', $enrollmentId)
            ->where('code_hash', $codeHash)
            ->update(['consumed_at' => now()]);
    }

    public function deleteByEnrollmentId(string $enrollmentId): bool
    {
        return (bool) BackupCodeModel::query()->where('enrollment_id', $enrollmentId)->delete();
    }
}
