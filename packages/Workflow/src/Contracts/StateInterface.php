<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Represents a workflow state definition.
 */
interface StateInterface
{
    /**
     * Get the state identifier.
     */
    public function getName(): string;

    /**
     * Get the state display label.
     */
    public function getLabel(): string;

    /**
     * Check if this is an initial state.
     */
    public function isInitial(): bool;

    /**
     * Check if this is a final state.
     */
    public function isFinal(): bool;

    /**
     * Get metadata associated with this state.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Get automation rules for this state (escalation, SLA, etc.).
     *
     * @return array<string, mixed>
     */
    public function getAutomation(): array;
}
