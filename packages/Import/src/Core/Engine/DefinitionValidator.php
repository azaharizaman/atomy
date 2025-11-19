<?php

declare(strict_types=1);

namespace Nexus\Import\Core\Engine;

use Nexus\Import\Contracts\ImportValidatorInterface;
use Nexus\Import\ValueObjects\ImportDefinition;
use Nexus\Import\ValueObjects\ValidationRule;
use Nexus\Import\ValueObjects\ImportError;
use Nexus\Import\ValueObjects\ErrorSeverity;
use Nexus\Import\Exceptions\InvalidDefinitionException;

/**
 * Import validator implementation
 * 
 * Validates imported data against business rules and schema.
 */
final class DefinitionValidator implements ImportValidatorInterface
{
    public function validateRow(
        array $row,
        array $rules,
        int $rowNumber
    ): array {
        $errors = [];

        foreach ($rules as $rule) {
            $value = $row[$rule->field] ?? null;
            
            if (!$this->validateRule($value, $rule)) {
                $errors[] = new ImportError(
                    rowNumber: $rowNumber,
                    field: $rule->field,
                    severity: ErrorSeverity::ERROR,
                    message: $rule->getErrorMessage(),
                    context: ['original_value' => $value]
                );
            }
        }

        return $errors;
    }

    public function validateDefinition(ImportDefinition $definition): array
    {
        $errors = [];

        // Check if definition has headers
        if (empty($definition->headers)) {
            $errors[] = 'Import definition must have at least one header';
        }

        // Check if definition has rows
        if ($definition->isEmpty()) {
            $errors[] = 'Import definition has no data rows';
        }

        // Validate row structure consistency
        $headerCount = count($definition->headers);
        foreach ($definition->rows as $index => $row) {
            $rowKeys = array_keys($row);
            
            if (count($rowKeys) !== $headerCount) {
                $errors[] = sprintf(
                    'Row %d has %d columns, expected %d',
                    $index + 1,
                    count($rowKeys),
                    $headerCount
                );
            }
        }

        return $errors;
    }

    public function validateDefinitionOrFail(ImportDefinition $definition): void
    {
        $errors = $this->validateDefinition($definition);
        
        if (!empty($errors)) {
            throw new InvalidDefinitionException($errors);
        }
    }

    public function isValid(array $row, array $rules): bool
    {
        foreach ($rules as $rule) {
            $value = $row[$rule->field] ?? null;
            
            if (!$this->validateRule($value, $rule)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate a single value against a rule
     */
    private function validateRule(mixed $value, ValidationRule $rule): bool
    {
        return match($rule->type) {
            'required' => !empty($value) || $value === '0' || $value === 0,
            'email' => $this->validateEmail($value),
            'numeric' => is_numeric($value),
            'integer' => $this->validateInteger($value),
            'min' => $this->validateMin($value, $rule->constraint),
            'max' => $this->validateMax($value, $rule->constraint),
            'min_length' => $this->validateMinLength($value, $rule->constraint),
            'max_length' => $this->validateMaxLength($value, $rule->constraint),
            'date' => $this->validateDate($value),
            'boolean' => $this->validateBoolean($value),
            'unique' => true,  // Handled by duplicate detector
            default => true
        };
    }

    private function validateEmail(mixed $value): bool
    {
        if (empty($value)) {
            return true;  // Use 'required' rule to enforce non-empty
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validateInteger(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    private function validateMin(mixed $value, mixed $constraint): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return (float) $value >= (float) $constraint;
    }

    private function validateMax(mixed $value, mixed $constraint): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return (float) $value <= (float) $constraint;
    }

    private function validateMinLength(mixed $value, mixed $constraint): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return mb_strlen((string) $value, 'UTF-8') >= (int) $constraint;
    }

    private function validateMaxLength(mixed $value, mixed $constraint): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return mb_strlen((string) $value, 'UTF-8') <= (int) $constraint;
    }

    private function validateDate(mixed $value): bool
    {
        if (empty($value)) {
            return true;
        }

        try {
            new \DateTimeImmutable((string) $value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function validateBoolean(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $normalized = mb_strtolower(trim((string) $value), 'UTF-8');
        
        return in_array($normalized, ['true', 'false', '1', '0', 'yes', 'no', 'y', 'n', 'on', 'off'], true);
    }
}
