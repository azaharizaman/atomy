<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\WorkflowDefinition;
use Nexus\Workflow\Exceptions\WorkflowDefinitionNotFoundException;
use Nexus\Workflow\Contracts\{WorkflowDefinitionInterface, DefinitionRepositoryInterface};

/**
 * Eloquent implementation of DefinitionRepositoryInterface
 */
final readonly class DbDefinitionRepository implements DefinitionRepositoryInterface
{
    public function findById(string $id): WorkflowDefinitionInterface
    {
        $definition = WorkflowDefinition::find($id);
        
        if (!$definition) {
            throw WorkflowDefinitionNotFoundException::withId($id);
        }
        
        return $definition;
    }

    public function findByName(string $name): WorkflowDefinitionInterface
    {
        $definition = WorkflowDefinition::where('name', $name)->first();
        
        if (!$definition) {
            throw WorkflowDefinitionNotFoundException::withName($name);
        }
        
        return $definition;
    }

    public function findActive(): array
    {
        return WorkflowDefinition::where('is_active', true)
            ->get()
            ->all();
    }

    public function save(WorkflowDefinitionInterface $definition): void
    {
        if ($definition instanceof WorkflowDefinition) {
            $definition->save();
        }
    }

    public function delete(string $id): void
    {
        WorkflowDefinition::destroy($id);
    }
}
