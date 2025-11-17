<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\WorkflowTimer;
use Nexus\Workflow\Contracts\{TimerInterface, TimerRepositoryInterface};

/**
 * Eloquent implementation of TimerRepositoryInterface
 */
final readonly class DbTimerRepository implements TimerRepositoryInterface
{
    public function findById(string $id): TimerInterface
    {
        return WorkflowTimer::findOrFail($id);
    }

    public function findDue(): array
    {
        return WorkflowTimer::where('is_fired', false)
            ->where('trigger_at', '<=', now())
            ->orderBy('trigger_at', 'asc')
            ->get()
            ->all();
    }

    public function findByWorkflow(string $workflowId): array
    {
        return WorkflowTimer::where('workflow_id', $workflowId)
            ->orderBy('trigger_at', 'asc')
            ->get()
            ->all();
    }

    public function save(TimerInterface $timer): void
    {
        if ($timer instanceof WorkflowTimer) {
            $timer->save();
        }
    }

    public function delete(string $id): void
    {
        WorkflowTimer::destroy($id);
    }

    public function markFired(string $id): void
    {
        WorkflowTimer::where('id', $id)->update([
            'is_fired' => true,
            'fired_at' => now(),
        ]);
    }
}
