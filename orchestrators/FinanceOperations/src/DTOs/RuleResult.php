<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result of a business rule evaluation.
 *
 * Represents the outcome of evaluating a business rule within
 * a workflow, including pass/fail status and any violations.
 *
 * @since 1.0.0
 */
final readonly class RuleResult
{
    /**
     * @param bool $passed Whether the rule passed
     * @param string $ruleName Name of the evaluated rule
     * @param string|null $message Human-readable result message
     * @param array<string> $violations List of violation details
     */
    public function __construct(
        public bool $passed,
        public string $ruleName,
        public ?string $message = null,
        public array $violations = [],
    ) {}

    /**
     * Check if the rule passed.
     *
     * @return bool
     */
    public function isPassed(): bool
    {
        return $this->passed;
    }

    /**
     * Create a passed rule result.
     *
     * @param string $ruleName Name of the rule
     * @return self
     */
    public static function passed(string $ruleName): self
    {
        return new self(
            passed: true,
            ruleName: $ruleName,
            message: null,
            violations: [],
        );
    }

    /**
     * Create a failed rule result.
     *
     * @param string $ruleName Name of the rule
     * @param string $message Failure message
     * @param array<string> $violations List of specific violations
     * @return self
     */
    public static function failed(string $ruleName, string $message, array $violations = []): self
    {
        return new self(
            passed: false,
            ruleName: $ruleName,
            message: $message,
            violations: $violations,
        );
    }
}
