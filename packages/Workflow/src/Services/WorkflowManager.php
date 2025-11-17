<?php

declare(strict_types=1);

namespace Nexus\Workflow\Services;

use Nexus\Workflow\Contracts\{
    WorkflowInterface,
    WorkflowRepositoryInterface,
    WorkflowDefinitionInterface,
    DefinitionRepositoryInterface,
    HistoryRepositoryInterface
};
use Nexus\Workflow\Core\StateEngine;
use Nexus\Workflow\Exceptions\WorkflowNotFoundException;

/**
 * Workflow Manager - Primary workflow service.
 *
 * Public API for workflow operations.
 */
final readonly class WorkflowManager
{
    public function __construct(
        private WorkflowRepositoryInterface $workflowRepository,
        private DefinitionRepositoryInterface $definitionRepository,
        private HistoryRepositoryInterface $historyRepository,
        private StateEngine $stateEngine
    ) {}

    /**
     * Instantiate a new workflow.
     *
     * @param array<string, mixed> $data
     */
    public function instantiate(
        string $definitionId,
        string $subjectType,
        string $subjectId,
        array $data = []
    ): WorkflowInterface {
        $definition = $this->definitionRepository->findById($definitionId);
        
        // Create workflow instance - implementation in repository
        // This is a skeleton showing the contract usage
        throw new \RuntimeException('Implementation required in repository layer');
    }

    /**
     * Apply a transition to a workflow.
     *
     * @throws \Nexus\Workflow\Exceptions\InvalidTransitionException
     * @throws \Nexus\Workflow\Exceptions\GuardConditionFailedException
     * @throws \Nexus\Workflow\Exceptions\WorkflowLockedException
     */
    public function apply(
        string $workflowId,
        string $transitionName,
        ?string $actorId = null,
        ?string $comment = null
    ): WorkflowInterface {
        $workflow = $this->workflowRepository->findById($workflowId);
        
        if ($workflow->isLocked()) {
            throw new \Nexus\Workflow\Exceptions\WorkflowLockedException::forWorkflow($workflowId);
        }
        
        $definition = $this->definitionRepository->findById($workflow->getDefinitionId());
        $transitions = $definition->getAvailableTransitions($workflow->getCurrentState());
        
        $transition = null;
        foreach ($transitions as $t) {
            if ($t->getName() === $transitionName) {
                $transition = $t;
                break;
            }
        }
        
        if (!$transition) {
            throw new \Nexus\Workflow\Exceptions\InvalidTransitionException::notDefined($transitionName);
        }
        
        // Validate and execute transition
        $this->stateEngine->applyTransition($workflow, $transition, $definition);
        
        // Update workflow state and save
        // Record history
        // Fire events
        
        return $workflow;
    }

    /**
     * Check if transition is allowed.
     */
    public function can(string $workflowId, string $transitionName): bool
    {
        try {
            $workflow = $this->workflowRepository->findById($workflowId);
            $definition = $this->definitionRepository->findById($workflow->getDefinitionId());
            $transitions = $definition->getAvailableTransitions($workflow->getCurrentState());
            
            foreach ($transitions as $transition) {
                if ($transition->getName() === $transitionName) {
                    return $this->stateEngine->canTransition($workflow, $transition);
                }
            }
            
            return false;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get workflow history.
     *
     * @return \Nexus\Workflow\Contracts\HistoryInterface[]
     */
    public function history(string $workflowId): array
    {
        return $this->historyRepository->findByWorkflow($workflowId);
    }

    /**
     * Lock workflow (prevents transitions).
     */
    public function lock(string $workflowId): void
    {
        $this->workflowRepository->lock($workflowId);
    }

    /**
     * Unlock workflow.
     */
    public function unlock(string $workflowId): void
    {
        $this->workflowRepository->unlock($workflowId);
    }
}
