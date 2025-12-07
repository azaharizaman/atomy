<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules;

/**
 * Result of a rule validation check.
 *
 * Encapsulates the outcome of a single business rule validation.
 */
final readonly class RuleResult
{
    /**
     * @param bool $passed Whether the rule passed
     * @param string $ruleName Name of the rule that was checked
     * @param string|null $message Human-readable message (especially for failures)
     * @param array<string, mixed> $context Additional context about the validation
     */
    private function __construct(
        public bool $passed,
        public string $ruleName,
        public ?string $message = null,
        public array $context = [],
    ) {}

    /**
     * Create a passing result.
     */
    public static function pass(string $ruleName, ?string $message = null, array $context = []): self
    {
        return new self(
            passed: true,
            ruleName: $ruleName,
            message: $message,
            context: $context,
        );
    }

    /**
     * Create a failing result.
     */
    public static function fail(string $ruleName, string $message, array $context = []): self
    {
        return new self(
            passed: false,
            ruleName: $ruleName,
            message: $message,
            context: $context,
        );
    }

    /**
     * Check if the rule failed.
     */
    public function failed(): bool
    {
        return !$this->passed;
    }

    /**
     * Get the failure message (or null if passed).
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }
}
