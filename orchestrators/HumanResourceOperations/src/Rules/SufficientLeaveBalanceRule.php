<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Rules;

use Nexus\HumanResourceOperations\DTOs\LeaveContext;
use Nexus\HumanResourceOperations\DTOs\RuleCheckResult;
use Nexus\HumanResourceOperations\Contracts\LeaveRuleInterface;

/**
 * Rule: Employee must have sufficient leave balance.
 */
final readonly class SufficientLeaveBalanceRule implements LeaveRuleInterface
{
    public function getName(): string
    {
        return 'sufficient_leave_balance';
    }

    public function getDescription(): string
    {
        return 'Employee must have sufficient leave balance for the requested period';
    }

    public function check(LeaveContext $context): RuleCheckResult
    {
        $passed = $context->currentBalance >= $context->daysRequested;

        return new RuleCheckResult(
            ruleName: $this->getName(),
            passed: $passed,
            severity: $passed ? 'INFO' : 'ERROR',
            message: $passed
                ? sprintf('Sufficient balance: %.2f days available', $context->currentBalance)
                : sprintf(
                    'Insufficient balance: %.2f days requested, %.2f available',
                    $context->daysRequested,
                    $context->currentBalance
                ),
            details: [
                'current_balance' => $context->currentBalance,
                'days_requested' => $context->daysRequested,
            ],
        );
    }
}
