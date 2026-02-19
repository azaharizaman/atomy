<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Contracts;

/**
 * Defines contract for workflow/saga state persistence.
 */
interface WorkflowStorageInterface
{
    /**
     * Save saga state.
     *
     * @param SagaStateInterface $state State to save
     */
    public function saveSagaState(SagaStateInterface $state): void;

    /**
     * Load saga state by instance ID.
     *
     * @param string $instanceId Saga instance ID
     * @return SagaStateInterface|null State or null if not found
     */
    public function loadSagaState(string $instanceId): ?SagaStateInterface;

    /**
     * Delete saga state.
     *
     * @param string $instanceId Saga instance ID
     */
    public function deleteSagaState(string $instanceId): void;

    /**
     * Get all saga instances for a tenant.
     *
     * @param string $tenantId Tenant ID
     * @param int $limit Maximum results
     * @param int $offset Offset for pagination
     * @return array<SagaStateInterface>
     */
    public function getTenantSagas(string $tenantId, int $limit = 100, int $offset = 0): array;

    /**
     * Get saga instances by status.
     *
     * @param string $tenantId Tenant ID
     * @param string $status Status to filter by
     * @return array<SagaStateInterface>
     */
    public function getSagasByStatus(string $tenantId, string $status): array;
}
