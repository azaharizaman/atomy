<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules;

/**
 * Contract for business rule validators.
 *
 * Rules are single-responsibility validators that check
 * one specific business constraint. They are composable
 * and can be combined in RuleRegistry classes.
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * - Each rule validates ONE constraint
 * - Rules are testable in isolation
 * - Rules can be reused across coordinators
 */
interface RuleInterface
{
    /**
     * Check if the rule passes for the given context.
     *
     * @param object $context The context object containing data to validate
     * @return RuleResult The result of the validation
     */
    public function check(object $context): RuleResult;

    /**
     * Get the unique name of this rule.
     *
     * @return string Rule identifier
     */
    public function getName(): string;
}
