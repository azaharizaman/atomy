<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ConsolidationEntry;

final readonly class EloquentConsolidationEntryRepository
{
    public function __construct(
        private ConsolidationEntry $model
    ) {}

    public function save(array $data): ConsolidationEntry
    {
        return $this->model->newQuery()->create($data);
    }

    public function findById(string $id): ?ConsolidationEntry
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByStatementId(string $statementId): array
    {
        return $this->model->newQuery()
            ->where('parent_statement_id', $statementId)
            ->orderBy('created_at')
            ->get()
            ->all();
    }

    public function findByEntities(string $sourceEntityId, string $targetEntityId): array
    {
        return $this->model->newQuery()
            ->where('source_entity_id', $sourceEntityId)
            ->where('target_entity_id', $targetEntityId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function findByRuleType(string $ruleType): array
    {
        return $this->model->newQuery()
            ->where('rule_type', $ruleType)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function deleteById(string $id): void
    {
        $this->model->newQuery()->where('id', $id)->delete();
    }

    public function deleteByStatementId(string $statementId): void
    {
        $this->model->newQuery()->where('parent_statement_id', $statementId)->delete();
    }
}
