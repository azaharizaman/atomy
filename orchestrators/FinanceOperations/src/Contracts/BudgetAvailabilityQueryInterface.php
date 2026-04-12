<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface BudgetAvailabilityQueryInterface
{
    public function getBudget(string $tenantId, string $budgetId): ?BudgetRuleViewInterface;

    public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string;
}
