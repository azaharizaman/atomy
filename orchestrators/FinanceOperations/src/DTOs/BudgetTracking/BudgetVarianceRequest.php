<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\BudgetTracking;

/**
 * Request DTO for budget variance analysis.
 *
 * Used to analyze variances between budgeted and actual
 * amounts for a specific period.
 *
 * @since 1.0.0
 */
final readonly class BudgetVarianceRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $periodId Accounting period for analysis
     * @param string|null $budgetId Specific budget (null = all budgets)
     * @param string|null $costCenterId Filter by cost center
     * @param string|null $projectId Filter by project (Tier 2+ feature)
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public ?string $budgetId = null,
        public ?string $costCenterId = null,
        public ?string $projectId = null,
    ) {}
}
