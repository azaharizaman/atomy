<?php

declare(strict_types=1);

namespace Nexus\Workflow\Core;

use Nexus\Workflow\Contracts\{
    WorkflowInterface,
    TransitionInterface,
    WorkflowDefinitionInterface,
    ConditionEvaluatorInterface
};
use Nexus\Workflow\Exceptions\{InvalidTransitionException, GuardConditionFailedException};

/**
 * Internal state machine engine.
 *
 * Executes state transitions with guard validation.
 */
final readonly class StateEngine
{
    public function __construct(
        private ConditionEvaluatorInterface $conditionEvaluator
    ) {}

    /**
     * Execute a state transition.
     *
     * @throws InvalidTransitionException
     * @throws GuardConditionFailedException
     */
    public function applyTransition(
        WorkflowInterface $workflow,
        TransitionInterface $transition,
        WorkflowDefinitionInterface $definition
    ): void {
        // Validate transition is allowed from current state
        if (!in_array($workflow->getCurrentState(), $transition->getFromStates(), true)) {
            throw InvalidTransitionException::fromState(
                $transition->getName(),
                $workflow->getCurrentState()
            );
        }

        // Evaluate guard condition if present
        if ($guard = $transition->getGuard()) {
            $context = $workflow->getData();
            if (!$this->conditionEvaluator->evaluate($guard, $context)) {
                throw GuardConditionFailedException::forTransition($transition->getName());
            }
        }

        // Transition logic will be implemented by WorkflowManager
    }

    /**
     * Check if transition is allowed.
     */
    public function canTransition(
        WorkflowInterface $workflow,
        TransitionInterface $transition
    ): bool {
        if (!in_array($workflow->getCurrentState(), $transition->getFromStates(), true)) {
            return false;
        }

        if ($guard = $transition->getGuard()) {
            try {
                $context = $workflow->getData();
                return $this->conditionEvaluator->evaluate($guard, $context);
            } catch (\Exception) {
                return false;
            }
        }

        return true;
    }
}
