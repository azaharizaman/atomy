<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\BudgetTracking;

/**
 * Result DTO for budget availability check.
 *
 * Contains budget availability status and utilization
 * metrics for budget control decisions.
 *
 * @since 1.0.0
 */
final readonly class BudgetCheckResult
{
    /**
     * @param bool $available Whether the requested amount is available
     * @param string $budgetId Budget identifier
     * @param string $budgeted Total budgeted amount (as string to avoid float precision issues)
     * @param string $actual Actual expenditure to date
     * @param string $committed Committed but not yet spent
     * @param string $availableAmount Remaining available budget
     * @param float $utilizationPercent Budget utilization percentage
     * @param string|null $warning Warning message if approaching threshold
     */
    public function __construct(
        public bool $available,
        public string $budgetId,
        public string $budgeted = '0',
        public string $actual = '0',
        public string $committed = '0',
        public string $availableAmount = '0',
        public float $utilizationPercent = 0.0,
        public ?string $warning = null,
    ) {}
}
