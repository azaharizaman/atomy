<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\WorkflowHistory;
use Nexus\Workflow\Contracts\{HistoryInterface, HistoryRepositoryInterface};

/**
 * Eloquent implementation of HistoryRepositoryInterface
 */
final readonly class DbHistoryRepository implements HistoryRepositoryInterface
{
    public function findByWorkflow(string $workflowId): array
    {
        return WorkflowHistory::where('workflow_id', $workflowId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function findByActor(string $actorId): array
    {
        return WorkflowHistory::where('actor_id', $actorId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function save(HistoryInterface $history): void
    {
        if ($history instanceof WorkflowHistory) {
            $history->save();
        }
    }
}
