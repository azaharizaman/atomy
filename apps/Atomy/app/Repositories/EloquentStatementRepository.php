<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\FinancialStatement;
use Nexus\Accounting\Contracts\FinancialStatementInterface;
use Nexus\Accounting\Contracts\StatementRepositoryInterface;
use Nexus\Accounting\Core\Enums\StatementType;
use Nexus\Accounting\Core\ValueObjects\ReportingPeriod;
use Nexus\Accounting\Exceptions\StatementNotFoundException;

final readonly class EloquentStatementRepository implements StatementRepositoryInterface
{
    public function __construct(
        private FinancialStatement $model
    ) {}

    public function save(FinancialStatementInterface $statement): void
    {
        $data = [
            'id' => $statement->getId(),
            'statement_type' => $statement->getType()->value,
            'entity_id' => $statement->getEntityId(),
            'period_id' => $statement->getReportingPeriod()->getId(),
            'data' => json_encode($statement->toArray()),
            'version' => $statement->getVersion(),
            'compliance_standard' => $statement->getComplianceStandard()?->value,
            'generated_at' => $statement->getGeneratedAt(),
            'generated_by' => $statement->getGeneratedBy(),
            'locked' => $statement->isLocked(),
        ];

        $this->model->newQuery()->updateOrCreate(['id' => $statement->getId()], $data);
    }

    public function findById(string $id): ?FinancialStatementInterface
    {
        $model = $this->model->newQuery()->find($id);

        if ($model === null) {
            return null;
        }

        return $this->mapToStatement($model);
    }

    public function findByEntityAndPeriod(
        string $entityId,
        string $periodId,
        StatementType $type
    ): ?FinancialStatementInterface {
        $model = $this->model->newQuery()
            ->where('entity_id', $entityId)
            ->where('period_id', $periodId)
            ->where('statement_type', $type->value)
            ->latest('version')
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->mapToStatement($model);
    }

    public function findLatestVersion(string $entityId, StatementType $type): ?FinancialStatementInterface
    {
        $model = $this->model->newQuery()
            ->where('entity_id', $entityId)
            ->where('statement_type', $type->value)
            ->latest('generated_at')
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->mapToStatement($model);
    }

    public function findVersionHistory(
        string $entityId,
        string $periodId,
        StatementType $type
    ): array {
        $models = $this->model->newQuery()
            ->where('entity_id', $entityId)
            ->where('period_id', $periodId)
            ->where('statement_type', $type->value)
            ->orderBy('version', 'desc')
            ->get();

        return $models->map(fn($model) => $this->mapToStatement($model))->all();
    }

    public function lockStatement(string $id): void
    {
        $updated = $this->model->newQuery()
            ->where('id', $id)
            ->update(['locked' => true]);

        if ($updated === 0) {
            throw new StatementNotFoundException("Statement {$id} not found");
        }
    }

    public function unlockStatement(string $id): void
    {
        $updated = $this->model->newQuery()
            ->where('id', $id)
            ->update(['locked' => false]);

        if ($updated === 0) {
            throw new StatementNotFoundException("Statement {$id} not found");
        }
    }

    public function deleteById(string $id): void
    {
        $deleted = $this->model->newQuery()->where('id', $id)->delete();

        if ($deleted === 0) {
            throw new StatementNotFoundException("Statement {$id} not found");
        }
    }

    /**
     * Map Eloquent model to domain interface implementation.
     */
    private function mapToStatement(FinancialStatement $model): FinancialStatementInterface
    {
        // This would typically reconstruct the appropriate Statement object
        // from the stored JSON data. For now, return the model itself as it
        // implements the interface.
        return $model;
    }
}
