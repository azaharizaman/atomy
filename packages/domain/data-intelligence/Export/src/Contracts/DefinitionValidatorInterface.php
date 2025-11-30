<?php

declare(strict_types=1);

namespace Nexus\Export\Contracts;

use Nexus\Export\ValueObjects\ExportDefinition;

/**
 * Definition validator contract
 * 
 * Validates ExportDefinition against schema rules before processing.
 * Ensures formatters receive well-formed, validated data.
 */
interface DefinitionValidatorInterface
{
    /**
     * Validate export definition against schema
     * 
     * @param ExportDefinition $definition Definition to validate
     * @return array<string, mixed> Validation errors (empty if valid)
     */
    public function validate(ExportDefinition $definition): array;

    /**
     * Check if definition is valid
     * 
     * @param ExportDefinition $definition Definition to check
     * @return bool True if valid, false otherwise
     */
    public function isValid(ExportDefinition $definition): bool;

    /**
     * Get supported schema version
     * 
     * @return string Schema version (e.g., '1.0')
     */
    public function getSchemaVersion(): string;

    /**
     * Validate and throw exception on failure
     * 
     * @param ExportDefinition $definition Definition to validate
     * @throws \Nexus\Export\Exceptions\InvalidDefinitionException
     */
    public function validateOrFail(ExportDefinition $definition): void;
}
