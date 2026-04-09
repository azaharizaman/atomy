<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Query contract used by budget availability rule checks.
 */
interface BudgetAvailabilityQueryInterface
{
    /**
     * @return object|null Budget aggregate or null when not found
     */
    public function getBudget(string $tenantId, string $budgetId): ?object;

    /**
     * @return numeric-string Available amount in budget currency
     */
    public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string;
}
