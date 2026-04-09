<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\BudgetAvailabilityQueryInterface;
use Nexus\FinanceOperations\Contracts\BudgetAvailableRuleInterface;
use Nexus\FinanceOperations\DTOs\RuleContexts\BudgetAvailableRuleContext;
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
     * @param bool $strictMode If true, fails when budget exceeded; if false, passes with warning
     */
    public function __construct(
        private BudgetAvailabilityQueryInterface $budgetQuery,
        private bool $strictMode = true,
    ) {}

    /**
     * @inheritDoc
     *
     */
    public function check(BudgetAvailableRuleContext $context): RuleResult
    {
        $tenantId = trim($context->tenantId);
        $budgetId = trim($context->budgetId);
        $requestedAmount = trim($context->amount);
        $costCenterId = $context->costCenterId;

        if ($tenantId === '') {
            return RuleResult::failed(
                $this->getName(),
                'Tenant ID is required for budget availability validation',
                ['missing_field' => 'tenantId']
            );
        }

        if (empty($budgetId)) {
            return RuleResult::failed(
                $this->getName(),
                'Budget ID is required for budget availability validation',
                ['missing_field' => 'budgetId']
            );
        }

        if (!is_numeric($requestedAmount)) {
            return RuleResult::failed(
                $this->getName(),
                'Amount must be numeric for budget availability validation',
                ['invalid_field' => 'amount']
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

        if (!$this->isBudgetActive($budget)) {
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

    /**
     * Check if the budget is active.
     *
     * @param object $budget The budget object
     * @return bool True if the budget is active
     */
    private function isBudgetActive(object $budget): bool
    {
        if (method_exists($budget, 'isActive')) {
            return $budget->isActive();
        }

        if (method_exists($budget, 'getIsActive')) {
            return $budget->getIsActive();
        }

        if (property_exists($budget, 'isActive')) {
            return $budget->isActive;
        }

        if (property_exists($budget, 'is_active')) {
            return $budget->is_active;
        }

        // Default to true if we cannot determine status
        return true;
    }
}
