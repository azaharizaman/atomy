<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Rules;

use Nexus\AccountPeriodClose\Contracts\CloseRuleInterface;
use Nexus\AccountPeriodClose\Enums\ValidationSeverity;
use Nexus\AccountPeriodClose\ValueObjects\CloseCheckResult;

/**
 * Rule to ensure trial balance is balanced (debits = credits).
 */
final readonly class TrialBalanceMustBalanceRule implements CloseRuleInterface
{
    private const float TOLERANCE = 0.01;

    public function getId(): string
    {
        return 'trial_balance_must_balance';
    }

    public function getName(): string
    {
        return 'Trial Balance Must Balance';
    }

    public function check(array $context): CloseCheckResult
    {
        $trialBalance = $context['trial_balance'] ?? [];

        if (empty($trialBalance)) {
            return CloseCheckResult::fail('Trial balance data not available');
        }

        $totalDebits = 0.0;
        $totalCredits = 0.0;

        foreach ($trialBalance as $accountCode => $balance) {
            if ($balance > 0) {
                $totalDebits += $balance;
            } else {
                $totalCredits += abs($balance);
            }
        }

        $difference = abs($totalDebits - $totalCredits);

        if ($difference > self::TOLERANCE) {
            return CloseCheckResult::fail(
                sprintf(
                    'Trial balance is out of balance by %.2f (Debits: %.2f, Credits: %.2f)',
                    $difference,
                    $totalDebits,
                    $totalCredits
                ),
                [
                    'difference' => $difference,
                    'total_debits' => $totalDebits,
                    'total_credits' => $totalCredits,
                ]
            );
        }

        return CloseCheckResult::pass();
    }

    public function getSeverity(): ValidationSeverity
    {
        return ValidationSeverity::CRITICAL;
    }
}
