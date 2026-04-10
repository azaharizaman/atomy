<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\BudgetAvailabilityQueryInterface;
use Nexus\FinanceOperations\Contracts\BudgetAvailableRuleInterface;
use Nexus\FinanceOperations\Contracts\RuleContextInterface;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Rule to validate that sufficient budget is available.
 *
 * This rule checks if the requested amount is within the available
 * budget for a given cost center or account.
 *
 * Following Advanced Orchestrator Pattern:
 * - Single responsibility: Budget availability validation
 * - Testable in isolation
 * - Reusable across coordinators
 *
 * @see ARCHITECTURE.md Section 4 for rule patterns
 * @since 1.0.0
 */
final readonly class BudgetAvailableRule implements BudgetAvailableRuleInterface
{
    /**
     * @param BudgetAvailabilityQueryInterface $budgetQuery Budget query for rule validation
     * @param bool $strictMode If true, fails when budget exceeded; if false, passes with warning
     */
    public function __construct(
        private BudgetAvailabilityQueryInterface $budgetQuery,
        private bool $strictMode = true,
    ) {}

    /**
     * @inheritDoc
     *
     * @param RuleContextInterface $context Context containing tenantId, budgetId, amount, and optional costCenterId
     * @return RuleResult The rule check result
     */
    public function check(RuleContextInterface $context): RuleResult
    {
        $tenantId = $context->getTenantId();
        $budgetId = trim((string) $context->getBudgetId());
        $amount = $context->getAmount();
        $costCenterId = $context->getCostCenterId();

        if ($amount === null || trim($amount) === '' || !is_numeric($amount)) {
            return RuleResult::failed(
                $this->getName(),
                'Amount is required and must be numeric for budget availability validation',
                ['missing_field' => 'amount']
            );
        }

        $requestedAmount = trim($amount);

        if (empty($budgetId)) {
            return RuleResult::failed(
                $this->getName(),
                'Budget ID is required for budget availability validation',
                ['missing_field' => 'budgetId']
            );
        }

        $budget = $this->budgetQuery->getBudget($tenantId, $budgetId);

        if ($budget === null) {
            return RuleResult::failed(
                $this->getName(),
                sprintf('Budget %s not found', $budgetId),
                ['budget_id' => $budgetId]
            );
        }

        if (!$budget->isActive()) {
            return RuleResult::failed(
                $this->getName(),
                sprintf('Budget %s is not active', $budgetId),
                ['budget_id' => $budgetId]
            );
        }

        $available = $this->budgetQuery->getAvailableAmount(
            $tenantId,
            $budgetId,
            $costCenterId
        );

        $requested = (float) $requestedAmount;
        $availableAmount = (float) $available;

        if ($requested > $availableAmount) {
            $violation = [
                'budget_id' => $budgetId,
                'cost_center_id' => $costCenterId,
                'requested' => (string) $requestedAmount,
                'available' => (string) $available,
                'shortfall' => (string) ($requested - $availableAmount),
            ];

            if ($this->strictMode) {
                return RuleResult::failed(
                    $this->getName(),
                    sprintf(
                        'Insufficient budget: requested %s, available %s',
                        $requestedAmount,
                        $available
                    ),
                    [$violation]
                );
            }

            // In non-strict mode, return passed with warning in violations
            return new RuleResult(
                passed: true,
                ruleName: $this->getName(),
                message: sprintf(
                    'Budget warning: requested %s exceeds available %s',
                    $requestedAmount,
                    $available
                ),
                violations: [$violation],
            );
        }

        return RuleResult::passed($this->getName());
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'budget_available';
    }
}
