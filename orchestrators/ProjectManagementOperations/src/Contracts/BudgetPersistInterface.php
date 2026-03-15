<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;

interface BudgetPersistInterface
{
    /**
     * Update earned revenue for a project (tenant-scoped; filter by tenantId in implementations).
     */
    public function updateEarnedRevenue(string $tenantId, string $projectId, Money $amount): void;
}
