<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;

/**
 * Contract for budget query operations.
 * Consumer application must implement this interface.
 */
interface BudgetQueryInterface
{
    public function getDepartmentBudget(string $tenantId, string $departmentId): ?Money;
}
