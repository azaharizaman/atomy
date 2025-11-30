<?php

declare(strict_types=1);

namespace Nexus\Import\Contracts;

/**
 * Data transformer contract
 * 
 * Applies transformation rules to normalize/clean data before validation.
 */
interface TransformerInterface
{
    /**
     * Transform a value using specified rules
     * 
     * Rules are applied in order. If a rule fails, returns the original value
     * and logs the transformation error (does not throw).
     * 
     * Standard rules:
     * - String: 'trim', 'upper', 'lower', 'capitalize', 'slug'
     * - Type: 'to_bool', 'to_int', 'to_float', 'to_string'
     * - Date: 'date_format', 'parse_date'
     * - Utility: 'concat', 'split', 'default', 'coalesce'
     * 
     * @param mixed $value Original value
     * @param array<string> $rules Transformation rules to apply
     * @param int $rowNumber Row number (for error reporting)
     * @param string $fieldName Field name (for error reporting)
     * @return array{value: mixed, errors: array<\Nexus\Import\ValueObjects\ImportError>}
     */
    public function transform(
        mixed $value,
        array $rules,
        int $rowNumber,
        string $fieldName
    ): array;

    /**
     * Check if a transformation rule is supported
     */
    public function supportsRule(string $rule): bool;

    /**
     * Get all supported transformation rules
     * 
     * @return array<string>
     */
    public function getSupportedRules(): array;

    /**
     * Register a custom transformation rule
     * 
     * @param string $name Rule name
     * @param callable $transformer Function(mixed $value): mixed
     */
    public function registerRule(string $name, callable $transformer): void;
}
