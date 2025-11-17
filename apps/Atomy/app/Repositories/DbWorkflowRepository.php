<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\WorkflowInstance;
use Nexus\Workflow\Exceptions\WorkflowNotFoundException;
use Nexus\Workflow\Contracts\{WorkflowInterface, WorkflowRepositoryInterface};

/**
 * Eloquent implementation of WorkflowRepositoryInterface
 */
final readonly class DbWorkflowRepository implements WorkflowRepositoryInterface
{
    public function findById(string $id): WorkflowInterface
    {
        $workflow = WorkflowInstance::find($id);
        
        if (!$workflow) {
            throw WorkflowNotFoundException::withId($id);
        }
        
        return $workflow;
    }

    public function findBySubject(string $subjectType, string $subjectId): array
    {
        return WorkflowInstance::where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->get()
            ->all();
    }

    public function findByDefinition(string $definitionId): array
    {
        return WorkflowInstance::where('definition_id', $definitionId)
            ->get()
            ->all();
    }

    public function findByState(string $state): array
    {
        return WorkflowInstance::where('current_state', $state)
            ->get()
            ->all();
    }

    public function save(WorkflowInterface $workflow): void
    {
        if ($workflow instanceof WorkflowInstance) {
            $workflow->save();
        }
    }

    public function delete(string $id): void
    {
        WorkflowInstance::destroy($id);
    }

    public function lock(string $id): void
    {
        WorkflowInstance::where('id', $id)->update(['is_locked' => true]);
    }

    public function unlock(string $id): void
    {
        WorkflowInstance::where('id', $id)->update(['is_locked' => false]);
    }
}
