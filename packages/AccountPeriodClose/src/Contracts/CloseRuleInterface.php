<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Contracts;

use Nexus\AccountPeriodClose\ValueObjects\CloseCheckResult;

/**
 * Base contract for period close rules.
 */
interface CloseRuleInterface
{
    /**
     * Get the rule identifier.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get the rule name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Check if the rule passes.
     *
     * @param array<string, mixed> $context
     * @return CloseCheckResult
     */
    public function check(array $context): CloseCheckResult;

    /**
     * Get the rule severity.
     *
     * @return \Nexus\AccountPeriodClose\Enums\ValidationSeverity
     */
    public function getSeverity(): \Nexus\AccountPeriodClose\Enums\ValidationSeverity;
}
