<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\BudgetTracking;

/**
 * Result DTO for budget threshold monitoring.
 *
 * Contains results of checking budgets against threshold
 * percentages, including exceeded thresholds and warnings.
 *
 * @since 1.0.0
 */
final readonly class BudgetThresholdResult
{
    /**
     * @param bool $success Whether the check operation succeeded
     * @param array<int, array{budgetId: string, budgetName: string, costCenterId: string|null, threshold: int, utilizationPercent: float, budgeted: string, actual: string, exceededAt: string}> $exceededThresholds Budgets that have exceeded thresholds
     * @param array<string> $warnings Warning messages for budgets approaching thresholds
     * @param string|null $error Error message if operation failed
     */
    public function __construct(
        public bool $success,
        public array $exceededThresholds = [],
        public array $warnings = [],
        public ?string $error = null,
    ) {}
}
