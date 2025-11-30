<?php

declare(strict_types=1);

namespace Nexus\Statutory\Core\Contracts;

/**
 * Interface for schema validation engine.
 */
interface SchemaValidatorInterface
{
    /**
     * Validate data against a schema.
     *
     * @param string $schemaIdentifier The schema identifier
     * @param array<string, mixed> $data The data to validate
     * @return array<string> Validation errors (empty if valid)
     */
    public function validate(string $schemaIdentifier, array $data): array;

    /**
     * Register a custom schema.
     *
     * @param string $schemaIdentifier The schema identifier
     * @param array<string, mixed> $schema The schema definition
     * @return void
     */
    public function registerSchema(string $schemaIdentifier, array $schema): void;

    /**
     * Check if a schema is registered.
     *
     * @param string $schemaIdentifier The schema identifier
     * @return bool
     */
    public function hasSchema(string $schemaIdentifier): bool;
}
