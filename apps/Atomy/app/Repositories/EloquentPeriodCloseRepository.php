<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PeriodClose;
use Nexus\Accounting\Core\Enums\PeriodCloseStatus;
use Nexus\Accounting\Exceptions\PeriodCloseNotFoundException;

final readonly class EloquentPeriodCloseRepository
{
    public function __construct(
        private PeriodClose $model
    ) {}

    public function save(array $data): PeriodClose
    {
        return $this->model->newQuery()->updateOrCreate(
            ['period_id' => $data['period_id']],
            $data
        );
    }

    public function findById(string $id): ?PeriodClose
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByPeriodId(string $periodId): ?PeriodClose
    {
        return $this->model->newQuery()->where('period_id', $periodId)->first();
    }

    public function findByStatus(PeriodCloseStatus $status): array
    {
        return $this->model->newQuery()
            ->where('status', $status->value)
            ->orderBy('closed_at', 'desc')
            ->get()
            ->all();
    }

    public function findByCloseType(string $closeType): array
    {
        return $this->model->newQuery()
            ->where('close_type', $closeType)
            ->orderBy('closed_at', 'desc')
            ->get()
            ->all();
    }

    public function updateStatus(string $periodId, PeriodCloseStatus $status, array $additionalData = []): void
    {
        $updated = $this->model->newQuery()
            ->where('period_id', $periodId)
            ->update(array_merge(['status' => $status->value], $additionalData));

        if ($updated === 0) {
            throw new PeriodCloseNotFoundException("Period close for {$periodId} not found");
        }
    }

    public function deleteByPeriodId(string $periodId): void
    {
        $deleted = $this->model->newQuery()->where('period_id', $periodId)->delete();

        if ($deleted === 0) {
            throw new PeriodCloseNotFoundException("Period close for {$periodId} not found");
        }
    }
}
