<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\RuleInterface;
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
final readonly class BudgetAvailableRule implements RuleInterface
{
    /**
     * @param object $budgetQuery BudgetQueryInterface for budget operations
     * @param bool $strictMode If true, fails when budget exceeded; if false, passes with warning
     */
    public function __construct(
        private object $budgetQuery,
        private bool $strictMode = true,
    ) {}

    /**
     * @inheritDoc
     *
     * @param object $context Context containing tenantId, budgetId, amount, and optional costCenterId
     * @return RuleResult The rule check result
     */
    public function check(object $context): RuleResult
    {
        $tenantId = $this->extractTenantId($context);
        $budgetId = $this->extractBudgetId($context);
        $requestedAmount = $this->extractAmount($context);
        $costCenterId = $this->extractCostCenterId($context);

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
     * Extract tenant ID from context.
     *
     * @param object $context The context object
     * @return string The tenant ID
     */
    private function extractTenantId(object $context): string
    {
        if (method_exists($context, 'getTenantId')) {
            return $context->getTenantId();
        }

        if (property_exists($context, 'tenantId')) {
            return $context->tenantId ?? '';
        }

        if (property_exists($context, 'tenant_id')) {
            return $context->tenant_id ?? '';
        }

        return '';
    }

    /**
     * Extract budget ID from context.
     *
     * @param object $context The context object
     * @return string The budget ID
     */
    private function extractBudgetId(object $context): string
    {
        if (method_exists($context, 'getBudgetId')) {
            return $context->getBudgetId();
        }

        if (property_exists($context, 'budgetId')) {
            return $context->budgetId ?? '';
        }

        if (property_exists($context, 'budget_id')) {
            return $context->budget_id ?? '';
        }

        return '';
    }

    /**
     * Extract amount from context.
     *
     * @param object $context The context object
     * @return string The amount
     */
    private function extractAmount(object $context): string
    {
        if (method_exists($context, 'getAmount')) {
            return (string) $context->getAmount();
        }

        if (property_exists($context, 'amount')) {
            return (string) $context->amount;
        }

        if (property_exists($context, 'requestedAmount')) {
            return (string) $context->requestedAmount;
        }

        return '0';
    }

    /**
     * Extract cost center ID from context.
     *
     * @param object $context The context object
     * @return string|null The cost center ID or null
     */
    private function extractCostCenterId(object $context): ?string
    {
        if (method_exists($context, 'getCostCenterId')) {
            return $context->getCostCenterId();
        }

        if (property_exists($context, 'costCenterId')) {
            return $context->costCenterId;
        }

        if (property_exists($context, 'cost_center_id')) {
            return $context->cost_center_id;
        }

        return null;
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
