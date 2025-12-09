<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\SpendPolicy;

use Nexus\ProcurementOperations\Enums\PolicyAction;

/**
 * Result DTO for spend policy evaluation.
 */
final readonly class SpendPolicyResult
{
    /**
     * @param bool $passed Whether all policy checks passed
     * @param PolicyAction $recommendedAction Recommended action to take
     * @param array<SpendPolicyViolation> $violations List of policy violations (if any)
     * @param array<string> $passedPolicies Names of policies that passed
     * @param array<string, mixed> $metadata Additional context data
     */
    public function __construct(
        public bool $passed,
        public PolicyAction $recommendedAction,
        public array $violations = [],
        public array $passedPolicies = [],
        public array $metadata = [],
    ) {}

    /**
     * Create a passing result.
     *
     * @param array<string> $passedPolicies Names of policies that passed
     */
    public static function pass(array $passedPolicies = []): self
    {
        return new self(
            passed: true,
            recommendedAction: PolicyAction::ALLOW,
            violations: [],
            passedPolicies: $passedPolicies,
        );
    }

    /**
     * Create a failing result.
     *
     * @param array<SpendPolicyViolation> $violations Policy violations
     * @param PolicyAction $recommendedAction Recommended action
     * @param array<string> $passedPolicies Names of policies that passed
     */
    public static function fail(
        array $violations,
        PolicyAction $recommendedAction = PolicyAction::BLOCK,
        array $passedPolicies = [],
    ): self {
        return new self(
            passed: false,
            recommendedAction: $recommendedAction,
            violations: $violations,
            passedPolicies: $passedPolicies,
        );
    }

    /**
     * Check if there are any violations.
     */
    public function hasViolations(): bool
    {
        return count($this->violations) > 0;
    }

    /**
     * Get the number of violations.
     */
    public function getViolationCount(): int
    {
        return count($this->violations);
    }

    /**
     * Get violations that require override approval.
     *
     * @return array<SpendPolicyViolation>
     */
    public function getOverridableViolations(): array
    {
        return array_filter(
            $this->violations,
            fn(SpendPolicyViolation $v) => $v->isOverridable
        );
    }

    /**
     * Get blocking violations.
     *
     * @return array<SpendPolicyViolation>
     */
    public function getBlockingViolations(): array
    {
        return array_filter(
            $this->violations,
            fn(SpendPolicyViolation $v) => $v->severity->isBlocking()
        );
    }

    /**
     * Check if transaction can proceed with override approval.
     */
    public function canProceedWithOverride(): bool
    {
        $blockingViolations = $this->getBlockingViolations();
        if (count($blockingViolations) === 0) {
            return true;
        }
        // Only allow override if ALL blocking violations are overridable
        foreach ($blockingViolations as $violation) {
            if (!$violation->isOverridable) {
                return false;
            }
        }
        return true;
    }
}
