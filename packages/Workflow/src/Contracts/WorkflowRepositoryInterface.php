<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Persistence contract for workflow instances.
 */
interface WorkflowRepositoryInterface
{
    /**
     * Find a workflow by ID.
     *
     * @throws \Nexus\Workflow\Exceptions\WorkflowNotFoundException
     */
    public function findById(string $id): WorkflowInterface;

    /**
     * Find workflows by subject (model).
     *
     * @return WorkflowInterface[]
     */
    public function findBySubject(string $subjectType, string $subjectId): array;

    /**
     * Find workflows by definition ID.
     *
     * @return WorkflowInterface[]
     */
    public function findByDefinition(string $definitionId): array;

    /**
     * Find workflows in specific state.
     *
     * @return WorkflowInterface[]
     */
    public function findByState(string $state): array;

    /**
     * Save workflow instance.
     */
    public function save(WorkflowInterface $workflow): void;

    /**
     * Delete workflow instance.
     */
    public function delete(string $id): void;

    /**
     * Lock workflow (prevents concurrent modifications).
     */
    public function lock(string $id): void;

    /**
     * Unlock workflow.
     */
    public function unlock(string $id): void;
}
