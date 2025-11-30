<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Represents a state transition definition.
 */
interface TransitionInterface
{
    /**
     * Get the transition identifier.
     */
    public function getName(): string;

    /**
     * Get the source state(s).
     *
     * @return string[]
     */
    public function getFromStates(): array;

    /**
     * Get the target state.
     */
    public function getToState(): string;

    /**
     * Get the transition label.
     */
    public function getLabel(): string;

    /**
     * Get guard condition expression (returns null if no guard).
     */
    public function getGuard(): ?string;

    /**
     * Get metadata for this transition.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
}
