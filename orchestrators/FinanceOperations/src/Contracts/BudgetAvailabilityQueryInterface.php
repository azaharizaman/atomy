<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Orchestrator-facing contract for budget availability checks.
 */
interface BudgetAvailabilityQueryInterface
{
    /**
     * Retrieve a budget aggregate/value object by identifier.
     */
    public function getBudget(string $tenantId, string $budgetId): ?object;

    /**
     * Retrieve currently available amount for a budget scope.
     */
    public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string;
}
