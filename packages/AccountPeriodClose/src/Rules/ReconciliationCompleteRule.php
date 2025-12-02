<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Rules;

use Nexus\AccountPeriodClose\Contracts\CloseRuleInterface;
use Nexus\AccountPeriodClose\Enums\ValidationSeverity;
use Nexus\AccountPeriodClose\ValueObjects\CloseCheckResult;

/**
 * Rule to ensure all reconciliations are complete.
 */
final readonly class ReconciliationCompleteRule implements CloseRuleInterface
{
    public function getId(): string
    {
        return 'reconciliation_complete';
    }

    public function getName(): string
    {
        return 'Reconciliations Complete';
    }

    public function check(array $context): CloseCheckResult
    {
        $reconciliationsComplete = $context['reconciliations_complete'] ?? false;

        if (!$reconciliationsComplete) {
            return CloseCheckResult::fail(
                'Not all reconciliations are complete for this period',
                ['reconciliations_complete' => false]
            );
        }

        return CloseCheckResult::pass();
    }

    public function getSeverity(): ValidationSeverity
    {
        return ValidationSeverity::WARNING;
    }
}
