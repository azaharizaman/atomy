<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Interface for budget availability rule.
 *
 * Defines the contract for checking if sufficient budget is available
 * for a given transaction or allocation.
 *
 * @since 1.0.0
 */
interface BudgetAvailableRuleInterface
{
    /**
     * Check if budget is available for the given context.
     *
     * @param object $context Context containing tenantId, budgetId, amount, and optional costCenterId
     * @return RuleResult The rule check result
     */
    public function check(object $context): RuleResult;

    /**
     * Get the rule name.
     *
     * @return string
     */
    public function getName(): string;
}
