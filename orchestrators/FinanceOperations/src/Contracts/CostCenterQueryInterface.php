<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Query contract used by cost center active rule checks.
 */
interface CostCenterQueryInterface
{
    /**
     * @return object|null Cost center aggregate or null when not found
     */
    public function find(string $tenantId, string $costCenterId): ?object;
}
