<?php

declare(strict_types=1);

namespace Nexus\Setting\Services;

use Nexus\Setting\Contracts\SettingsSchemaRegistryInterface;
use Nexus\Setting\Exceptions\SettingValidationException;

/**
 * Settings validation service.
 *
 * This service validates setting values against registered schemas.
 */
class SettingsValidationService
{
    /**
     * Create a new validation service instance.
     */
    public function __construct(
        private readonly SettingsSchemaRegistryInterface $registry,
    ) {
    }

    /**
     * Validate a setting value against its schema.
     *
     * @param string $key The setting key
     * @param mixed $value The value to validate
     * @throws SettingValidationException If validation fails
     */
    public function validate(string $key, mixed $value): bool
    {
        $schema = $this->registry->get($key);

        if ($schema === null) {
            // No schema registered, skip validation
            return true;
        }

        $this->validateType($key, $value, $schema);
        $this->validateRules($key, $value, $schema);

        return true;
    }

    /**
     * Validate the value type.
     */
    private function validateType(string $key, mixed $value, array $schema): void
    {
        $expectedType = $schema['type'] ?? 'string';

        $valid = match ($expectedType) {
            'string' => is_string($value),
            'int', 'integer' => is_int($value),
            'bool', 'boolean' => is_bool($value),
            'float', 'double' => is_float($value) || is_int($value),
            'array' => is_array($value),
            'json' => is_array($value) || is_string($value),
            default => true,
        };

        if (! $valid) {
            throw new SettingValidationException(
                $key,
                "Expected type '{$expectedType}', got '" . gettype($value) . "'"
            );
        }
    }

    /**
     * Validate the value against schema rules.
     */
    private function validateRules(string $key, mixed $value, array $schema): void
    {
        $rules = $schema['validation_rules'] ?? $schema['validationRules'] ?? [];

        // Validate required (not null) - check this first before other validations
        if (isset($rules['required']) && $rules['required'] === true && $value === null) {
            throw new SettingValidationException(
                $key,
                "Value is required and cannot be null"
            );
        }

        // Validate minimum value/length
        if (isset($rules['min'])) {
            if (is_numeric($value) && $value < $rules['min']) {
                throw new SettingValidationException(
                    $key,
                    "Value must be at least {$rules['min']}"
                );
            }

            if (is_string($value) && strlen($value) < $rules['min']) {
                throw new SettingValidationException(
                    $key,
                    "Length must be at least {$rules['min']} characters"
                );
            }

            if (is_array($value) && count($value) < $rules['min']) {
                throw new SettingValidationException(
                    $key,
                    "Array must have at least {$rules['min']} items"
                );
            }
        }

        // Validate maximum value/length
        if (isset($rules['max'])) {
            if (is_numeric($value) && $value > $rules['max']) {
                throw new SettingValidationException(
                    $key,
                    "Value must not exceed {$rules['max']}"
                );
            }

            if (is_string($value) && strlen($value) > $rules['max']) {
                throw new SettingValidationException(
                    $key,
                    "Length must not exceed {$rules['max']} characters"
                );
            }

            if (is_array($value) && count($value) > $rules['max']) {
                throw new SettingValidationException(
                    $key,
                    "Array must not have more than {$rules['max']} items"
                );
            }
        }

        // Validate pattern (regex)
        if (isset($rules['pattern']) && is_string($value)) {
            if (! preg_match($rules['pattern'], $value)) {
                throw new SettingValidationException(
                    $key,
                    "Value does not match required pattern"
                );
            }
        }

        // Validate enum (allowed values)
        if (isset($rules['enum']) && is_array($rules['enum'])) {
            if (! in_array($value, $rules['enum'], true)) {
                $allowed = implode(', ', $rules['enum']);
                throw new SettingValidationException(
                    $key,
                    "Value must be one of: {$allowed}"
                );
            }
        }
    }
}
