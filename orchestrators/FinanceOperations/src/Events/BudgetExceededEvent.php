<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Events;

/**
 * Event dispatched when a budget is exceeded.
 * 
 * This event is triggered when a budget availability check finds that
 * the requested amount exceeds the available budget.
 * 
 * @since 1.0.0
 */
final readonly class BudgetExceededEvent
{
    /**
     * @param string $tenantId The tenant identifier
     * @param string $budgetId The budget identifier
     * @param string $requestedAmount The amount that was requested
     * @param string $availableAmount The amount that is available
     */
    public function __construct(
        public string $tenantId,
        public string $budgetId,
        public string $requestedAmount,
        public string $availableAmount,
    ) {}
}
