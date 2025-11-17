<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\WorkflowInstance;
use Nexus\Workflow\Services\WorkflowManager;

/**
 * HasWorkflow Trait
 *
 * Adds workflow capabilities to Eloquent models.
 *
 * Usage:
 * ```php
 * class PurchaseOrder extends Model
 * {
 *     use HasWorkflow;
 *     
 *     protected string $workflowDefinitionId = 'purchase_order_approval';
 * }
 * ```
 */
trait HasWorkflow
{
    /**
     * Get or create workflow instance for this model.
     */
    public function workflow(): WorkflowInstance
    {
        $existing = WorkflowInstance::where('subject_type', static::class)
            ->where('subject_id', $this->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Auto-instantiate workflow if definition is configured
        if (property_exists($this, 'workflowDefinitionId')) {
            $manager = app(WorkflowManager::class);
            return $manager->instantiate(
                $this->workflowDefinitionId,
                static::class,
                $this->id
            );
        }

        throw new \RuntimeException('No workflow instance found and no workflowDefinitionId configured.');
    }

    /**
     * Check if a transition can be applied.
     */
    public function canTransition(string $transition): bool
    {
        $manager = app(WorkflowManager::class);
        $workflow = $this->workflow();
        
        return $manager->can($workflow->id, $transition);
    }

    /**
     * Apply a transition.
     */
    public function applyTransition(string $transition, ?string $actorId = null, ?string $comment = null): WorkflowInstance
    {
        $manager = app(WorkflowManager::class);
        $workflow = $this->workflow();
        
        return $manager->apply($workflow->id, $transition, $actorId, $comment);
    }

    /**
     * Get workflow history.
     */
    public function workflowHistory(): array
    {
        $manager = app(WorkflowManager::class);
        $workflow = $this->workflow();
        
        return $manager->history($workflow->id);
    }

    /**
     * Get current workflow state.
     */
    public function workflowState(): string
    {
        return $this->workflow()->current_state;
    }
}
