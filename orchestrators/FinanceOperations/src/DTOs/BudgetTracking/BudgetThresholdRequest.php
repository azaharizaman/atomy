<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\BudgetTracking;

/**
 * Request DTO for budget threshold monitoring.
 *
 * Used to check budgets against defined threshold percentages
 * for proactive budget control alerts.
 *
 * @since 1.0.0
 */
final readonly class BudgetThresholdRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $periodId Accounting period to check
     * @param array<int> $thresholds Threshold percentages to check (e.g., [80, 90, 100])
     * @param string|null $costCenterId Filter by cost center
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public array $thresholds = [80, 90, 100],
        public ?string $costCenterId = null,
    ) {}
}
