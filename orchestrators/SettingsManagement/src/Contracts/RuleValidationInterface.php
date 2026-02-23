<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Contracts;

/**
 * Interface for validation rules.
 */
interface RuleValidationInterface
{
    /**
     * Evaluate the rule against the given context.
     */
    public function evaluate(mixed $context): RuleValidationResult;
}

/**
 * Result of rule validation.
 */
final class RuleValidationResult
{
    public function __construct(
        public readonly bool $passed,
        public readonly ?string $error = null,
        public readonly array $details = [],
    ) {}

    public static function success(array $details = []): self
    {
        return new self(passed: true, details: $details);
    }

    public static function failure(string $error, array $details = []): self
    {
        return new self(passed: false, error: $error, details: $details);
    }
}
