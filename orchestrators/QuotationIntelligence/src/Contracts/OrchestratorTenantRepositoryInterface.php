<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Port for tenant repository operations.
 */
interface OrchestratorTenantRepositoryInterface
{
    /**
     * Find tenant by ID.
     *
     * @param string $id
     * @return OrchestratorTenantInterface|null
     */
    public function findById(string $id): ?OrchestratorTenantInterface;
}
