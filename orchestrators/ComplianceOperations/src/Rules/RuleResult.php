<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Rules;

/**
 * Result of a compliance rule validation check.
 *
 * Encapsulates the outcome of a single business rule validation.
 *
 * @see ARCHITECTURE.md Section 3 for rule patterns
 */
final readonly class RuleResult
{
    /**
     * @param bool $passed Whether the rule passed
     * @param string $ruleName Name of the rule that was checked
     * @param string|null $message Human-readable message (especially for failures)
     * @param array<string, mixed> $context Additional context about the validation
     * @param string|null $severity Severity level for failures (error, warning, info)
     */
    private function __construct(
        public bool $passed,
        public string $ruleName,
        public ?string $message = null,
        public array $context = [],
        public ?string $severity = null,
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
            severity: null,
        );
    }

    /**
     * Create a failing result.
     */
    public static function fail(
        string $ruleName,
        string $message,
        array $context = [],
        string $severity = 'error'
    ): self {
        return new self(
            passed: false,
            ruleName: $ruleName,
            message: $message,
            context: $context,
            severity: $severity,
        );
    }

    /**
     * Create a warning result (passes but with warning).
     */
    public static function warn(string $ruleName, string $message, array $context = []): self
    {
        return new self(
            passed: true,
            ruleName: $ruleName,
            message: $message,
            context: $context,
            severity: 'warning',
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
     * Check if the rule has a warning.
     */
    public function hasWarning(): bool
    {
        return $this->passed && $this->severity === 'warning';
    }

    /**
     * Get the failure message (or null if passed).
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Get the severity level.
     */
    public function getSeverity(): ?string
    {
        return $this->severity;
    }

    /**
     * Get the context data.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
