<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\SpendPolicy;

use Nexus\ProcurementOperations\Contracts\SpendPolicyRuleInterface;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyContext;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyResult;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyViolation;
use Nexus\ProcurementOperations\Enums\PolicyAction;
use Nexus\ProcurementOperations\Enums\PolicyViolationSeverity;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Registry of spend policy rules.
 *
 * Manages the collection of policy rules and orchestrates their execution.
 */
final class SpendPolicyRuleRegistry
{
    /**
     * @var array<SpendPolicyRuleInterface>
     */
    private array $rules = [];

    /**
     * Create a registry with default rules.
     */
    public static function withDefaultRules(): self
    {
        $registry = new self();

        // Register all default spend policy rules
        $registry->register(new CategorySpendLimitRule());
        $registry->register(new VendorSpendLimitRule());
        $registry->register(new PreferredVendorRule());
        $registry->register(new MaverickSpendRule());
        $registry->register(new BudgetAvailabilityRule());
        $registry->register(new ContractComplianceRule());

        return $registry;
    }

    /**
     * Register a policy rule.
     */
    public function register(SpendPolicyRuleInterface $rule): void
    {
        $this->rules[$rule->getName()] = $rule;
    }

    /**
     * Unregister a policy rule.
     */
    public function unregister(string $ruleName): void
    {
        unset($this->rules[$ruleName]);
    }

    /**
     * Validate context against all applicable rules.
     *
     * @param SpendPolicyContext $context The evaluation context
     * @return SpendPolicyResult The combined result of all rule evaluations
     */
    public function validate(SpendPolicyContext $context): SpendPolicyResult
    {
        $violations = [];
        $passedPolicies = [];

        foreach ($this->rules as $rule) {
            // Skip non-applicable rules
            if (!$rule->isApplicable($context)) {
                continue;
            }

            $result = $rule->check($context);

            if ($result->passed) {
                $passedPolicies[] = $rule->getName();
                continue;
            }

            // Extract violation from result context
            $violation = $result->context['violation'] ?? null;
            if ($violation instanceof SpendPolicyViolation) {
                $violations[] = $violation;
            }
        }

        if (empty($violations)) {
            return SpendPolicyResult::pass($passedPolicies);
        }

        // Determine recommended action based on severity of violations
        $recommendedAction = $this->determineAction($violations);

        return SpendPolicyResult::fail(
            violations: $violations,
            recommendedAction: $recommendedAction,
            passedPolicies: $passedPolicies,
        );
    }

    /**
     * Get all registered rules.
     *
     * @return array<SpendPolicyRuleInterface>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Get a rule by name.
     */
    public function getRule(string $name): ?SpendPolicyRuleInterface
    {
        return $this->rules[$name] ?? null;
    }

    /**
     * Determine the recommended action based on violations.
     *
     * @param array<SpendPolicyViolation> $violations
     */
    private function determineAction(array $violations): PolicyAction
    {
        $maxSeverity = PolicyViolationSeverity::INFO;

        foreach ($violations as $violation) {
            if ($violation->severity->getWeight() > $maxSeverity->getWeight()) {
                $maxSeverity = $violation->severity;
            }
        }

        // Map severity to action
        return match ($maxSeverity) {
            PolicyViolationSeverity::INFO => PolicyAction::ALLOW,
            PolicyViolationSeverity::WARNING => PolicyAction::FLAG_FOR_REVIEW,
            PolicyViolationSeverity::ERROR => PolicyAction::REQUIRE_APPROVAL,
            PolicyViolationSeverity::CRITICAL => PolicyAction::BLOCK,
        };
    }
}
