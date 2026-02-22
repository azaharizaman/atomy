<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\BudgetTracking;

/**
 * Request DTO for budget availability check.
 *
 * Used to check if budget is available for a specific
 * expenditure amount against a budget.
 *
 * @since 1.0.0
 */
final readonly class BudgetCheckRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $budgetId Budget to check against
     * @param string $amount Amount to check (as string to avoid float precision issues)
     * @param string|null $costCenterId Optional cost center for detailed check
     * @param string|null $accountId Optional account for detailed check
     */
    public function __construct(
        public string $tenantId,
        public string $budgetId,
        public string $amount,
        public ?string $costCenterId = null,
        public ?string $accountId = null,
    ) {}
}
