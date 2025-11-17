<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Persistence contract for workflow timers.
 */
interface TimerRepositoryInterface
{
    /**
     * Find a timer by ID.
     */
    public function findById(string $id): TimerInterface;

    /**
     * Find all due timers (trigger_at <= now and not fired).
     *
     * @return TimerInterface[]
     */
    public function findDue(): array;

    /**
     * Find timers for a workflow.
     *
     * @return TimerInterface[]
     */
    public function findByWorkflow(string $workflowId): array;

    /**
     * Save timer.
     */
    public function save(TimerInterface $timer): void;

    /**
     * Delete timer.
     */
    public function delete(string $id): void;

    /**
     * Mark timer as fired.
     */
    public function markFired(string $id): void;
}
