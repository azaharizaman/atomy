<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyContext;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Contract for individual spend policy rules.
 *
 * Each rule validates one specific spend policy constraint.
 */
interface SpendPolicyRuleInterface
{
    /**
     * Check if the rule passes for the given context.
     *
     * @param SpendPolicyContext $context The policy evaluation context
     * @return RuleResult The result of the validation
     */
    public function check(SpendPolicyContext $context): RuleResult;

    /**
     * Get the unique name of this rule.
     *
     * @return string Rule identifier
     */
    public function getName(): string;

    /**
     * Get the policy type this rule enforces.
     *
     * @return string Policy type identifier
     */
    public function getPolicyType(): string;

    /**
     * Check if this rule should be evaluated for the given context.
     *
     * Some rules may not apply to certain transaction types or categories.
     *
     * @param SpendPolicyContext $context The policy evaluation context
     * @return bool True if the rule should be evaluated
     */
    public function isApplicable(SpendPolicyContext $context): bool;
}
