<?php

declare(strict_types=1);

namespace Nexus\Export\Core\Engine;

use Nexus\Export\Contracts\DefinitionValidatorInterface;
use Nexus\Export\Exceptions\InvalidDefinitionException;
use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportSection;
use Nexus\Export\ValueObjects\TableStructure;

/**
 * Definition validator implementation
 * 
 * Validates ExportDefinition against schema v1.0 constraints:
 * - Section nesting depth ≤8
 * - Table column consistency
 * - Required metadata fields
 * - Valid schema version
 */
final readonly class DefinitionValidator implements DefinitionValidatorInterface
{
    private const MAX_NESTING_DEPTH = 8;
    private const SUPPORTED_SCHEMA_VERSIONS = ['1.0'];

    /**
     * Validate export definition
     * 
     * @return array<string, string[]> Validation errors by field
     */
    public function validate(ExportDefinition $definition): array
    {
        $errors = [];

        // Validate schema version
        if (!in_array($definition->metadata->schemaVersion, self::SUPPORTED_SCHEMA_VERSIONS, true)) {
            $errors['metadata.schemaVersion'][] = sprintf(
                'Unsupported schema version: %s. Supported versions: %s',
                $definition->metadata->schemaVersion,
                implode(', ', self::SUPPORTED_SCHEMA_VERSIONS)
            );
        }

        // Validate required metadata
        if (empty($definition->metadata->title)) {
            $errors['metadata.title'][] = 'Title is required';
        }

        if ($definition->metadata->generatedAt === null) {
            $errors['metadata.generatedAt'][] = 'Generation timestamp is required';
        }

        // Validate structure
        if (empty($definition->structure)) {
            $errors['structure'][] = 'At least one section is required';
        } else {
            foreach ($definition->structure as $index => $section) {
                $sectionErrors = $this->validateSection($section);
                if (!empty($sectionErrors)) {
                    $errors["structure[{$index}]"] = $sectionErrors;
                }
            }
        }

        return $errors;
    }

    /**
     * Check if definition is valid
     */
    public function isValid(ExportDefinition $definition): bool
    {
        return empty($this->validate($definition));
    }

    /**
     * Get supported schema version
     */
    public function getSchemaVersion(): string
    {
        return '1.0';
    }

    /**
     * Validate or throw exception
     * 
     * @throws InvalidDefinitionException
     */
    public function validateOrFail(ExportDefinition $definition): void
    {
        $errors = $this->validate($definition);
        
        if (!empty($errors)) {
            throw InvalidDefinitionException::fromValidationErrors($errors);
        }
    }

    /**
     * Validate section recursively
     * 
     * @return string[] Validation errors
     */
    private function validateSection(ExportSection $section, int $depth = 0): array
    {
        $errors = [];

        // Check nesting depth
        if ($depth > self::MAX_NESTING_DEPTH) {
            $errors[] = sprintf(
                'Section nesting depth exceeds maximum of %d levels',
                self::MAX_NESTING_DEPTH
            );
            return $errors; // Stop validation at this branch
        }

        // Validate section level matches actual depth
        if ($section->level !== $depth) {
            $errors[] = sprintf(
                'Section level mismatch: declared level %d but at depth %d',
                $section->level,
                $depth
            );
        }

        // Validate items
        foreach ($section->items as $itemIndex => $item) {
            if ($item instanceof TableStructure) {
                $tableErrors = $this->validateTable($item);
                if (!empty($tableErrors)) {
                    $errors[] = sprintf('Table[%d]: %s', $itemIndex, implode('; ', $tableErrors));
                }
            } elseif ($item instanceof ExportSection) {
                $childErrors = $this->validateSection($item, $depth + 1);
                if (!empty($childErrors)) {
                    $errors[] = sprintf('Subsection[%d]: %s', $itemIndex, implode('; ', $childErrors));
                }
            }
            // Scalar items (string, array) are always valid
        }

        return $errors;
    }

    /**
     * Validate table structure
     * 
     * @return string[] Validation errors
     */
    private function validateTable(TableStructure $table): array
    {
        $errors = [];

        // Check column consistency (already validated in TableStructure constructor)
        // But we can add additional business rules here

        if (empty($table->headers)) {
            $errors[] = 'Table must have at least one header';
        }

        if (empty($table->rows)) {
            $errors[] = 'Table must have at least one row';
        }

        // Validate column widths if specified
        if (!empty($table->columnWidths)) {
            if (count($table->columnWidths) !== count($table->headers)) {
                $errors[] = sprintf(
                    'Column widths count (%d) does not match headers count (%d)',
                    count($table->columnWidths),
                    count($table->headers)
                );
            }

            foreach ($table->columnWidths as $index => $width) {
                if (!is_int($width) || $width <= 0) {
                    $errors[] = sprintf('Column width[%d] must be positive integer, got: %s', $index, $width);
                }
            }
        }

        return $errors;
    }
}
