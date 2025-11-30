<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Contract for workflow definition.
 *
 * Defines the structure of a workflow (states, transitions, etc.).
 */
interface WorkflowDefinitionInterface
{
    /**
     * Get the definition ID.
     */
    public function getId(): string;

    /**
     * Get the definition name.
     */
    public function getName(): string;

    /**
     * Get the definition version.
     */
    public function getVersion(): string;

    /**
     * Get all states.
     *
     * @return StateInterface[]
     */
    public function getStates(): array;

    /**
     * Get a specific state by name.
     *
     * @throws \Nexus\Workflow\Exceptions\StateNotFoundException
     */
    public function getState(string $name): StateInterface;

    /**
     * Get all transitions.
     *
     * @return TransitionInterface[]
     */
    public function getTransitions(): array;

    /**
     * Get available transitions from a specific state.
     *
     * @return TransitionInterface[]
     */
    public function getAvailableTransitions(string $fromState): array;

    /**
     * Get the initial state name.
     */
    public function getInitialState(): string;

    /**
     * Get the definition schema for data validation.
     *
     * @return array<string, mixed>
     */
    public function getDataSchema(): array;

    /**
     * Check if definition is active.
     */
    public function isActive(): bool;
}
