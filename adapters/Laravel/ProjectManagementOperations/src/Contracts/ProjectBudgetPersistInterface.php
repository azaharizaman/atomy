<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;

/**
 * Update earned revenue for a project. Implemented by the app or Budget adapter.
 * Tenant resolution must be enforced before any data operations (filter by tenantId in implementations).
 */
interface ProjectBudgetPersistInterface
{
    public function updateEarnedRevenue(string $tenantId, string $projectId, Money $amount): void;
}
