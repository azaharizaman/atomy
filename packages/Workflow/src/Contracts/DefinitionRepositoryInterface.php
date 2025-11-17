<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Persistence contract for workflow definitions.
 */
interface DefinitionRepositoryInterface
{
    /**
     * Find a definition by ID.
     *
     * @throws \Nexus\Workflow\Exceptions\WorkflowDefinitionNotFoundException
     */
    public function findById(string $id): WorkflowDefinitionInterface;

    /**
     * Find a definition by name.
     *
     * @throws \Nexus\Workflow\Exceptions\WorkflowDefinitionNotFoundException
     */
    public function findByName(string $name): WorkflowDefinitionInterface;

    /**
     * Find all active definitions.
     *
     * @return WorkflowDefinitionInterface[]
     */
    public function findActive(): array;

    /**
     * Save definition.
     */
    public function save(WorkflowDefinitionInterface $definition): void;

    /**
     * Delete definition.
     */
    public function delete(string $id): void;
}
