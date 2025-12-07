<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

/**
 * Defines contract for workflow state persistence.
 *
 * Consumers must implement this interface to persist workflow
 * and saga state to their chosen storage backend.
 */
interface WorkflowStorageInterface
{
    /**
     * Save a workflow state.
     *
     * @param WorkflowStateInterface $state State to save
     */
    public function saveWorkflowState(WorkflowStateInterface $state): void;

    /**
     * Load a workflow state by instance ID.
     *
     * @param string $instanceId The instance ID to load
     * @return WorkflowStateInterface|null State or null if not found
     */
    public function loadWorkflowState(string $instanceId): ?WorkflowStateInterface;

    /**
     * Delete a workflow state.
     *
     * @param string $instanceId The instance ID to delete
     */
    public function deleteWorkflowState(string $instanceId): void;

    /**
     * Save a saga state.
     *
     * @param SagaStateInterface $state State to save
     */
    public function saveSagaState(SagaStateInterface $state): void;

    /**
     * Load a saga state by instance ID.
     *
     * @param string $instanceId The instance ID to load
     * @param string|null $sagaId Optional saga definition ID for validation
     * @return SagaStateInterface|null State or null if not found
     */
    public function loadSagaState(string $instanceId, ?string $sagaId = null): ?SagaStateInterface;

    /**
     * Delete a saga state.
     *
     * @param string $instanceId The instance ID to delete
     */
    public function deleteSagaState(string $instanceId): void;

    /**
     * Find workflows by status.
     *
     * @param string $tenantId Tenant to query
     * @param string $workflowId Workflow definition ID
     * @param string $status Status to filter by
     * @return array<WorkflowStateInterface>
     */
    public function findWorkflowsByStatus(string $tenantId, string $workflowId, string $status): array;

    /**
     * Find sagas by status.
     *
     * @param string $tenantId Tenant to query
     * @param string $sagaId Saga definition ID
     * @param string $status Status to filter by
     * @return array<SagaStateInterface>
     */
    public function findSagasByStatus(string $tenantId, string $sagaId, string $status): array;
}
