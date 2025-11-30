<?php

declare(strict_types=1);

namespace Nexus\Statutory\Core\Engine;

use Nexus\Statutory\Core\Contracts\SchemaValidatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Schema validator for statutory report data.
 */
final class SchemaValidator implements SchemaValidatorInterface
{
    /**
     * @var array<string, array<string, mixed>> Registered schemas
     */
    private array $schemas = [];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function validate(string $schemaIdentifier, array $data): array
    {
        $this->logger->debug("Validating data against schema", [
            'schema_identifier' => $schemaIdentifier,
            'data_keys' => array_keys($data),
        ]);

        if (!isset($this->schemas[$schemaIdentifier])) {
            $this->logger->warning("Schema not found", [
                'schema_identifier' => $schemaIdentifier,
            ]);
            return ["Schema not found: {$schemaIdentifier}"];
        }

        $schema = $this->schemas[$schemaIdentifier];
        $errors = [];

        // Validate required fields
        if (isset($schema['required'])) {
            foreach ($schema['required'] as $field) {
                if (!isset($data[$field])) {
                    $errors[] = "Missing required field: {$field}";
                }
            }
        }

        // Validate field types
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $field => $definition) {
                if (!isset($data[$field])) {
                    continue;
                }

                $value = $data[$field];
                $type = $definition['type'] ?? 'string';

                $actualType = gettype($value);
                $expectedType = $this->mapJsonTypeToPhpType($type);

                if ($actualType !== $expectedType) {
                    $errors[] = "Field '{$field}' has wrong type: expected {$type}, got {$actualType}";
                }

                // Validate string patterns
                if ($type === 'string' && isset($definition['pattern'])) {
                    if (!preg_match($definition['pattern'], (string) $value)) {
                        $errors[] = "Field '{$field}' does not match required pattern";
                    }
                }

                // Validate numeric ranges
                if (in_array($type, ['integer', 'number'], true)) {
                    if (isset($definition['minimum']) && $value < $definition['minimum']) {
                        $errors[] = "Field '{$field}' is below minimum value";
                    }
                    if (isset($definition['maximum']) && $value > $definition['maximum']) {
                        $errors[] = "Field '{$field}' exceeds maximum value";
                    }
                }

                // Validate enums
                if (isset($definition['enum'])) {
                    if (!in_array($value, $definition['enum'], true)) {
                        $errors[] = "Field '{$field}' has invalid value";
                    }
                }
            }
        }

        if (empty($errors)) {
            $this->logger->info("Schema validation passed", [
                'schema_identifier' => $schemaIdentifier,
            ]);
        } else {
            $this->logger->warning("Schema validation failed", [
                'schema_identifier' => $schemaIdentifier,
                'error_count' => count($errors),
            ]);
        }

        return $errors;
    }

    public function registerSchema(string $schemaIdentifier, array $schema): void
    {
        $this->logger->info("Registering schema", [
            'schema_identifier' => $schemaIdentifier,
        ]);
        $this->schemas[$schemaIdentifier] = $schema;
    }

    public function hasSchema(string $schemaIdentifier): bool
    {
        return isset($this->schemas[$schemaIdentifier]);
    }

    /**
     * Map JSON Schema types to PHP types.
     *
     * @param string $jsonType JSON Schema type
     * @return string PHP type
     */
    private function mapJsonTypeToPhpType(string $jsonType): string
    {
        return match ($jsonType) {
            'string' => 'string',
            'integer' => 'integer',
            'number' => 'double',
            'boolean' => 'boolean',
            'array' => 'array',
            'object' => 'array',
            default => 'string',
        };
    }
}
