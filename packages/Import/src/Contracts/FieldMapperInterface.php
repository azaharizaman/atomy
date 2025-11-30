<?php

declare(strict_types=1);

namespace Nexus\Import\Contracts;

use Nexus\Import\ValueObjects\ImportDefinition;
use Nexus\Import\ValueObjects\FieldMapping;

/**
 * Field mapper contract
 * 
 * Maps source fields to target fields with transformation support.
 */
interface FieldMapperInterface
{
    /**
     * Map source data to target structure using field mappings
     * 
     * Applies transformations and handles default values.
     * 
     * @param array<string, mixed> $sourceRow Source row data
     * @param array<FieldMapping> $mappings Field mapping definitions
     * @param int $rowNumber Row number (for error reporting)
     * @return array{data: array<string, mixed>, errors: array<\Nexus\Import\ValueObjects\ImportError>}
     */
    public function map(
        array $sourceRow,
        array $mappings,
        int $rowNumber
    ): array;

    /**
     * Validate field mappings against import definition
     * 
     * @param ImportDefinition $definition Import definition with headers
     * @param array<FieldMapping> $mappings Field mappings to validate
     * @return array<string> Validation errors (empty if valid)
     */
    public function validateMappings(
        ImportDefinition $definition,
        array $mappings
    ): array;

    /**
     * Auto-generate field mappings based on header names
     * 
     * Creates 1:1 mappings for matching field names.
     * 
     * @param array<string> $sourceHeaders Headers from import file
     * @param array<string> $targetFields Available target field names
     * @return array<FieldMapping>
     */
    public function autoMap(
        array $sourceHeaders,
        array $targetFields
    ): array;
}
