<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * Result of a validation operation.
 */
final readonly class ValidationResult
{
    /**
     * @param array<int, array{rule: string, message: string, severity: string}> $errors
     */
    public function __construct(
        public bool $passed,
        public array $errors = [],
    ) {}

    /**
     * Create a passed validation result.
     */
    public static function passed(): self
    {
        return new self(passed: true);
    }

    /**
     * Create a failed validation result.
     *
     * @param array<int, array{rule: string, message: string, severity: string}> $errors
     */
    public static function failed(array $errors): self
    {
        return new self(passed: false, errors: $errors);
    }

    /**
     * Add an error to the result.
     */
    public function withError(string $rule, string $message, string $severity = 'error'): self
    {
        $errors = $this->errors;
        $errors[] = [
            'rule' => $rule,
            'message' => $message,
            'severity' => $severity,
        ];

        return new self(passed: false, errors: $errors);
    }
}
