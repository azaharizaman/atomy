<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\SpendPolicy;

use Nexus\ProcurementOperations\Contracts\SpendPolicyRuleInterface;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyContext;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyViolation;
use Nexus\ProcurementOperations\Enums\PolicyViolationSeverity;
use Nexus\ProcurementOperations\Enums\SpendPolicyType;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Rule to enforce budget availability.
 *
 * Validates that sufficient budget is available for the
 * department or cost center.
 */
final readonly class BudgetAvailabilityRule implements SpendPolicyRuleInterface
{
    private const string NAME = 'budget_availability';

    /**
     * @inheritDoc
     */
    public function check(SpendPolicyContext $context): RuleResult
    {
        // Skip if no department context
        if (!$context->request->hasDepartment()) {
            return RuleResult::pass(self::NAME, 'No department context for budget check');
        }

        // Skip if no budget defined
        if ($context->departmentBudget === null) {
            return RuleResult::pass(self::NAME, 'No budget defined for department');
        }

        // Check budget availability
        if ($context->budgetAvailable && !$context->wouldExceedDepartmentBudget()) {
            $remaining = $context->departmentBudget->subtract($context->getProjectedDepartmentSpend());
            return RuleResult::pass(self::NAME, sprintf(
                'Budget available. Remaining after transaction: %s',
                $remaining->format()
            ));
        }

        // Calculate overage
        $projectedSpend = $context->getProjectedDepartmentSpend();
        $overage = $projectedSpend->subtract($context->departmentBudget);

        $violation = SpendPolicyViolation::budgetUnavailable(
            budgetRemaining: $context->departmentBudget->subtract($context->departmentSpendYtd),
            requested: $context->request->amount,
            budgetId: $context->request->departmentId,
            severity: PolicyViolationSeverity::CRITICAL,
        );

        return RuleResult::fail(
            self::NAME,
            $violation->message,
            [
                'violation' => $violation,
                'overage' => $overage->format(),
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @inheritDoc
     */
    public function getPolicyType(): string
    {
        return SpendPolicyType::BUDGET_AVAILABILITY->value;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SpendPolicyContext $context): bool
    {
        return $context->request->hasDepartment()
            && $context->isPolicyEnabled(SpendPolicyType::BUDGET_AVAILABILITY->value);
    }
}
