<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\ValueObjects\ReportingPeriod;

/**
 * Contract for budget data access.
 *
 * Implementations must provide access to budget data
 * for variance analysis and reporting.
 */
interface BudgetRepositoryInterface
{
    /**
     * Get budget amounts for the specified period.
     *
     * @param ReportingPeriod $period The period to retrieve budget for
     * @param string|null $budgetId Optional specific budget ID
     * @param string|null $tenantId Optional tenant ID for multi-tenant systems
     *
     * @return array<string, array{account_id: string, amount: float, currency: string}>
     *
     * @throws \RuntimeException If retrieval fails
     */
    public function getBudgetForPeriod(
        ReportingPeriod $period,
        ?string $budgetId = null,
        ?string $tenantId = null
    ): array;

    /**
     * Get budget by ID.
     *
     * @param string $budgetId The budget ID
     * @param string|null $tenantId Optional tenant ID for multi-tenant systems
     *
     * @return array<string, mixed>|null The budget data or null if not found
     */
    public function findById(string $budgetId, ?string $tenantId = null): ?array;

    /**
     * Get all active budgets.
     *
     * @param string|null $tenantId Optional tenant ID for multi-tenant systems
     *
     * @return array<array<string, mixed>> List of active budgets
     */
    public function getActiveBudgets(?string $tenantId = null): array;
}
