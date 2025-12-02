<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects representing state.
 */
interface Stateful
{
    /**
     * Get the current state value.
     */
    public function getState(): string;

    /**
     * Check if transition to another state is allowed.
     */
    public function canTransitionTo(self $newState): bool;

    /**
     * Check if this is a final state.
     */
    public function isFinal(): bool;
}
