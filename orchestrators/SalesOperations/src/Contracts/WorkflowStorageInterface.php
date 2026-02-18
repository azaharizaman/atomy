<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface WorkflowStorageInterface
{
    public function saveWorkflowState(WorkflowStateInterface $state): void;

    public function loadWorkflowState(string $instanceId): ?WorkflowStateInterface;

    public function deleteWorkflowState(string $instanceId): void;

    public function saveSagaState(SagaStateInterface $state): void;

    public function loadSagaState(string $instanceId, ?string $sagaId = null): ?SagaStateInterface;

    public function deleteSagaState(string $instanceId): void;

    public function findWorkflowsByStatus(string $tenantId, string $workflowId, string $status): array;

    public function findSagasByStatus(string $tenantId, string $sagaId, string $status): array;
}
