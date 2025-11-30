<?php

declare(strict_types=1);

namespace Nexus\Import\Core\Engine;

use Nexus\Import\ValueObjects\ImportError;
use Nexus\Import\ValueObjects\ErrorSeverity;

/**
 * Data validator engine
 * 
 * Validates data against rules before import
 */
final class DataValidator
{
    /**
     * Validate data against rules
     * 
     * @param array $data Row data to validate
     * @param array $rules Validation rules
     * @return array<ImportError> Array of validation errors
     */
    public function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $error = $this->applyRule($field, $value, $rule);
                if ($error !== null) {
                    $errors[] = $error;
                }
            }
        }

        return $errors;
    }

    private function applyRule(string $field, mixed $value, string $rule): ?ImportError
    {
        [$ruleName, $params] = $this->parseRule($rule);

        return match ($ruleName) {
            'required' => $this->validateRequired($field, $value),
            'email' => $this->validateEmail($field, $value),
            'numeric' => $this->validateNumeric($field, $value),
            'min' => $this->validateMin($field, $value, (float)($params[0] ?? 0)),
            'max' => $this->validateMax($field, $value, (float)($params[0] ?? 0)),
            'regex' => $this->validateRegex($field, $value, $params[0] ?? ''),
            default => null,
        };
    }

    private function parseRule(string $rule): array
    {
        if (str_contains($rule, ':')) {
            [$name, $paramsString] = explode(':', $rule, 2);
            return [$name, explode(',', $paramsString)];
        }

        return [$rule, []];
    }

    private function validateRequired(string $field, mixed $value): ?ImportError
    {
        if ($value === null || $value === '') {
            return new ImportError(
                rowNumber: null,
                field: $field,
                severity: ErrorSeverity::ERROR,
                message: "Field '{$field}' is required",
                code: 'REQUIRED_FIELD_MISSING'
            );
        }

        return null;
    }

    private function validateEmail(string $field, mixed $value): ?ImportError
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return new ImportError(
                rowNumber: null,
                field: $field,
                severity: ErrorSeverity::ERROR,
                message: "Invalid email format: {$value}",
                code: 'INVALID_EMAIL'
            );
        }

        return null;
    }

    private function validateNumeric(string $field, mixed $value): ?ImportError
    {
        if ($value !== null && !is_numeric($value)) {
            return new ImportError(
                rowNumber: null,
                field: $field,
                severity: ErrorSeverity::ERROR,
                message: "Field '{$field}' must be numeric",
                code: 'NOT_NUMERIC'
            );
        }

        return null;
    }

    private function validateMin(string $field, mixed $value, float $min): ?ImportError
    {
        if ($value !== null && is_numeric($value) && (float)$value < $min) {
            return new ImportError(
                rowNumber: null,
                field: $field,
                severity: ErrorSeverity::ERROR,
                message: "Field '{$field}' must be at least {$min}",
                code: 'VALUE_TOO_SMALL'
            );
        }

        return null;
    }

    private function validateMax(string $field, mixed $value, float $max): ?ImportError
    {
        if ($value !== null && is_numeric($value) && (float)$value > $max) {
            return new ImportError(
                rowNumber: null,
                field: $field,
                severity: ErrorSeverity::ERROR,
                message: "Field '{$field}' must be at most {$max}",
                code: 'VALUE_TOO_LARGE'
            );
        }

        return null;
    }

    private function validateRegex(string $field, mixed $value, string $pattern): ?ImportError
    {
        if ($value !== null && !preg_match($pattern, (string)$value)) {
            return new ImportError(
                rowNumber: null,
                field: $field,
                severity: ErrorSeverity::ERROR,
                message: "Field '{$field}' does not match required pattern",
                code: 'PATTERN_MISMATCH'
            );
        }

        return null;
    }
}
