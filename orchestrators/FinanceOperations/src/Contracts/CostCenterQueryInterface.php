<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Orchestrator-facing contract to query cost centers.
 */
interface CostCenterQueryInterface
{
    /**
     * Find a cost center by tenant and identifier.
     */
    public function find(string $tenantId, string $costCenterId): ?object;
}
