<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\RuleContexts;

/**
 * Typed context for budget availability rule evaluation.
 */
final readonly class BudgetAvailableRuleContext
{
    public function __construct(
        public string $tenantId,
        public string $budgetId,
        public string $amount,
        public ?string $costCenterId = null,
    ) {}
}
