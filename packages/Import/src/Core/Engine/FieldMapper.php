<?php

declare(strict_types=1);

namespace Nexus\Import\Core\Engine;

use Nexus\Import\Contracts\FieldMapperInterface;
use Nexus\Import\Contracts\TransformerInterface;
use Nexus\Import\ValueObjects\ImportDefinition;
use Nexus\Import\ValueObjects\FieldMapping;
use Nexus\Import\ValueObjects\ImportError;
use Nexus\Import\ValueObjects\ErrorSeverity;

/**
 * Field mapper implementation
 * 
 * Maps source fields to target fields with transformation support.
 */
final readonly class FieldMapper implements FieldMapperInterface
{
    public function __construct(
        private TransformerInterface $transformer
    ) {}

    public function map(
        array $sourceRow,
        array $mappings,
        int $rowNumber
    ): array {
        $targetData = [];
        $errors = [];

        foreach ($mappings as $mapping) {
            $sourceValue = $sourceRow[$mapping->sourceField] ?? null;

            // Apply default value if source is null/empty
            if (($sourceValue === null || $sourceValue === '') && $mapping->defaultValue !== null) {
                $sourceValue = $mapping->defaultValue;
            }

            // Apply transformations if any
            if ($mapping->hasTransformations()) {
                $result = $this->transformer->transform(
                    $sourceValue,
                    $mapping->transformations,
                    $rowNumber,
                    $mapping->sourceField
                );
                
                $sourceValue = $result['value'];
                $errors = array_merge($errors, $result['errors']);
            }

            // Check required fields
            if ($mapping->required && ($sourceValue === null || $sourceValue === '')) {
                $errors[] = new ImportError(
                    rowNumber: $rowNumber,
                    field: $mapping->targetField,
                    severity: ErrorSeverity::ERROR,
                    message: "Required field '{$mapping->targetField}' is missing or empty",
                    context: ['original_value' => $sourceValue]
                );
            }

            $targetData[$mapping->targetField] = $sourceValue;
        }

        return ['data' => $targetData, 'errors' => $errors];
    }

    public function validateMappings(
        ImportDefinition $definition,
        array $mappings
    ): array {
        $errors = [];
        $availableHeaders = $definition->headers;

        foreach ($mappings as $index => $mapping) {
            // Check if source field exists in headers
            if (!in_array($mapping->sourceField, $availableHeaders, true)) {
                $errors[] = sprintf(
                    "Mapping #%d: Source field '%s' not found in import headers. Available: %s",
                    $index + 1,
                    $mapping->sourceField,
                    implode(', ', $availableHeaders)
                );
            }

            // Validate transformation rules
            if ($mapping->hasTransformations()) {
                foreach ($mapping->transformations as $rule) {
                    if (!$this->transformer->supportsRule($rule)) {
                        $errors[] = sprintf(
                            "Mapping #%d: Unsupported transformation rule '%s' for field '%s'",
                            $index + 1,
                            $rule,
                            $mapping->sourceField
                        );
                    }
                }
            }
        }

        return $errors;
    }

    public function autoMap(
        array $sourceHeaders,
        array $targetFields
    ): array {
        $mappings = [];

        foreach ($sourceHeaders as $header) {
            // Normalize field names for comparison (lowercase, replace spaces/dashes with underscores)
            $normalized = strtolower(preg_replace('/[\s\-]+/', '_', $header));
            
            foreach ($targetFields as $targetField) {
                $normalizedTarget = strtolower(preg_replace('/[\s\-]+/', '_', $targetField));
                
                if ($normalized === $normalizedTarget) {
                    $mappings[] = new FieldMapping(
                        sourceField: $header,
                        targetField: $targetField,
                        required: false
                    );
                    break;
                }
            }
        }

        return $mappings;
    }
}
