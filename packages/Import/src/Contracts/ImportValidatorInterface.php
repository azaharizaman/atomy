<?php

declare(strict_types=1);

namespace Nexus\Import\Contracts;

use Nexus\Import\ValueObjects\ImportDefinition;
use Nexus\Import\ValueObjects\ValidationRule;

/**
 * Import validator contract
 * 
 * Validates imported data against business rules.
 */
interface ImportValidatorInterface
{
    /**
     * Validate a single row against validation rules
     * 
     * @param array<string, mixed> $row Row data (after mapping)
     * @param array<ValidationRule> $rules Validation rules
     * @param int $rowNumber Row number (for error reporting)
     * @return array<\Nexus\Import\ValueObjects\ImportError> Validation errors
     */
    public function validateRow(
        array $row,
        array $rules,
        int $rowNumber
    ): array;

    /**
     * Validate entire import definition structure
     * 
     * Checks schema consistency (headers, data types, required fields).
     * 
     * @param ImportDefinition $definition Import definition to validate
     * @return array<string> Validation errors (empty if valid)
     */
    public function validateDefinition(ImportDefinition $definition): array;

    /**
     * Validate or fail (throws exception on validation failure)
     * 
     * @throws \Nexus\Import\Exceptions\InvalidDefinitionException
     */
    public function validateDefinitionOrFail(ImportDefinition $definition): void;

    /**
     * Check if validation rules are satisfied
     * 
     * @param array<string, mixed> $row Row data
     * @param array<ValidationRule> $rules Validation rules
     */
    public function isValid(array $row, array $rules): bool;
}
