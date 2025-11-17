<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Persistence contract for workflow history.
 */
interface HistoryRepositoryInterface
{
    /**
     * Find history entries for a workflow.
     *
     * @return HistoryInterface[]
     */
    public function findByWorkflow(string $workflowId): array;

    /**
     * Find history entries by actor.
     *
     * @return HistoryInterface[]
     */
    public function findByActor(string $actorId): array;

    /**
     * Save history entry.
     */
    public function save(HistoryInterface $history): void;
}
