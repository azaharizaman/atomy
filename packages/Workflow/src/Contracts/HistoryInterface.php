<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Contract for workflow history/audit tracking.
 */
interface HistoryInterface
{
    /**
     * Get the history entry ID.
     */
    public function getId(): string;

    /**
     * Get the workflow instance ID.
     */
    public function getWorkflowId(): string;

    /**
     * Get the transition name (null for non-transition events).
     */
    public function getTransition(): ?string;

    /**
     * Get the source state.
     */
    public function getFromState(): string;

    /**
     * Get the target state.
     */
    public function getToState(): string;

    /**
     * Get the user who triggered the transition.
     */
    public function getActorId(): ?string;

    /**
     * Get the comment provided with the transition.
     */
    public function getComment(): ?string;

    /**
     * Get metadata for this history entry.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Get the timestamp of the transition.
     */
    public function getCreatedAt(): \DateTimeInterface;
}
