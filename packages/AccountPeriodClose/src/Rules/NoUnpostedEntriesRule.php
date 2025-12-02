<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Rules;

use Nexus\AccountPeriodClose\Contracts\CloseRuleInterface;
use Nexus\AccountPeriodClose\Enums\ValidationSeverity;
use Nexus\AccountPeriodClose\ValueObjects\CloseCheckResult;

/**
 * Rule to ensure no unposted journal entries exist.
 */
final readonly class NoUnpostedEntriesRule implements CloseRuleInterface
{
    public function getId(): string
    {
        return 'no_unposted_entries';
    }

    public function getName(): string
    {
        return 'No Unposted Entries';
    }

    public function check(array $context): CloseCheckResult
    {
        $unpostedEntries = $context['unposted_entries'] ?? [];

        if (count($unpostedEntries) > 0) {
            return CloseCheckResult::fail(
                sprintf('There are %d unposted journal entries', count($unpostedEntries)),
                ['unposted_count' => count($unpostedEntries)]
            );
        }

        return CloseCheckResult::pass();
    }

    public function getSeverity(): ValidationSeverity
    {
        return ValidationSeverity::ERROR;
    }
}
