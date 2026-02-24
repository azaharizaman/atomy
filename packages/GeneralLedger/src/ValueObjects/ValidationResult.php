<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\ValueObjects;

/**
 * Validation Result
 * 
 * Value object representing the result of validation.
 */
final readonly class ValidationResult
{
    /**
     * @param bool $isValid Whether validation passed
     * @param array<string> $errors Validation errors
     * @param array<string, mixed> $warnings Validation warnings
     */
    private function __construct(
        public bool $isValid,
        public array $errors,
        public array $warnings,
    ) {}

    /**
     * Create a successful validation result
     */
    public static function valid(array $warnings = []): self
    {
        return new self(isValid: true, errors: [], warnings: $warnings);
    }

    /**
     * Create a failed validation result
     */
    public static function invalid(array $errors, array $warnings = []): self
    {
        return new self(isValid: false, errors: $errors, warnings: $warnings);
    }
}
