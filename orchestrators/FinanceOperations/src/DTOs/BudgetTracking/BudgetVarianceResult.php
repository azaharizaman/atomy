<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\BudgetTracking;

/**
 * Result DTO for budget variance analysis.
 *
 * Contains variance analysis results comparing budgeted
 * amounts with actual expenditures.
 *
 * @since 1.0.0
 */
final readonly class BudgetVarianceResult
{
    /**
     * @param bool $success Whether the analysis succeeded
     * @param array<int, array{budgetId: string, budgetName: string, costCenterId: string|null, accountId: string|null, budgeted: string, actual: string, variance: string, variancePercent: float, status: string}> $variances Detailed variance breakdown
     * @param string $totalBudgeted Total budgeted amount (as string to avoid float precision issues)
     * @param string $totalActual Total actual expenditure
     * @param string $totalVariance Total variance amount
     * @param string|null $error Error message if operation failed
     */
    public function __construct(
        public bool $success,
        public array $variances = [],
        public string $totalBudgeted = '0',
        public string $totalActual = '0',
        public string $totalVariance = '0',
        public ?string $error = null,
    ) {}
}
