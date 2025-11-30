<?php

declare(strict_types=1);

namespace Nexus\Import\Core\Engine;

use Nexus\Import\Contracts\TransformerInterface;
use Nexus\Import\ValueObjects\ImportError;
use Nexus\Import\ValueObjects\ErrorSeverity;

/**
 * Data transformer implementation
 * 
 * Applies transformation rules to normalize data before validation.
 */
final class DataTransformer implements TransformerInterface
{
    /**
     * @var array<string, callable>
     */
    private array $customRules = [];

    public function transform(
        mixed $value,
        array $rules,
        int $rowNumber,
        string $fieldName
    ): array {
        $errors = [];
        $transformedValue = $value;

        foreach ($rules as $rule) {
            try {
                $transformedValue = $this->applyRule($transformedValue, $rule);
            } catch (\Throwable $e) {
                // Transformation failed - collect error but don't throw
                $errors[] = new ImportError(
                    rowNumber: $rowNumber,
                    field: $fieldName,
                    severity: ErrorSeverity::ERROR,
                    message: "Transformation '{$rule}' failed: {$e->getMessage()}",
                    context: ['original_value' => $value, 'transformation_rule' => $rule]
                );
                
                // Return original value on transformation failure
                return ['value' => $value, 'errors' => $errors];
            }
        }

        return ['value' => $transformedValue, 'errors' => $errors];
    }

    public function supportsRule(string $rule): bool
    {
        return isset($this->customRules[$rule]) || method_exists($this, 'transform' . ucfirst($rule));
    }

    public function getSupportedRules(): array
    {
        $builtIn = [
            'trim', 'upper', 'lower', 'capitalize', 'slug',
            'to_bool', 'to_int', 'to_float', 'to_string',
            'date_format', 'parse_date',
            'default', 'coalesce'
        ];

        return array_merge($builtIn, array_keys($this->customRules));
    }

    public function registerRule(string $name, callable $transformer): void
    {
        $this->customRules[$name] = $transformer;
    }

    /**
     * Apply a single transformation rule
     */
    private function applyRule(mixed $value, string $rule): mixed
    {
        // Check custom rules first
        if (isset($this->customRules[$rule])) {
            return ($this->customRules[$rule])($value);
        }

        // Built-in rules
        return match($rule) {
            // String transformations
            'trim' => $this->transformTrim($value),
            'upper' => $this->transformUpper($value),
            'lower' => $this->transformLower($value),
            'capitalize' => $this->transformCapitalize($value),
            'slug' => $this->transformSlug($value),
            
            // Type conversions
            'to_bool' => $this->transformToBool($value),
            'to_int' => $this->transformToInt($value),
            'to_float' => $this->transformToFloat($value),
            'to_string' => $this->transformToString($value),
            
            // Date transformations
            'date_format' => $this->transformDateFormat($value),
            'parse_date' => $this->transformParseDate($value),
            
            // Utility
            'default' => $this->transformDefault($value),
            'coalesce' => $this->transformCoalesce($value),
            
            default => throw new \InvalidArgumentException("Unsupported transformation rule: {$rule}")
        };
    }

    // String transformations
    private function transformTrim(mixed $value): string
    {
        return trim((string) $value);
    }

    private function transformUpper(mixed $value): string
    {
        return mb_strtoupper((string) $value, 'UTF-8');
    }

    private function transformLower(mixed $value): string
    {
        return mb_strtolower((string) $value, 'UTF-8');
    }

    private function transformCapitalize(mixed $value): string
    {
        $str = mb_strtolower((string) $value, 'UTF-8');
        return mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');
    }

    private function transformSlug(mixed $value): string
    {
        $slug = mb_strtolower((string) $value, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }

    // Type conversions
    private function transformToBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = mb_strtolower(trim((string) $value), 'UTF-8');
        
        return match($normalized) {
            'true', '1', 'yes', 'y', 'on' => true,
            'false', '0', 'no', 'n', 'off', '' => false,
            default => (bool) $value
        };
    }

    private function transformToInt(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        // Remove common formatting (commas, spaces)
        $cleaned = preg_replace('/[,\s]/', '', (string) $value);
        
        if (!is_numeric($cleaned)) {
            throw new \InvalidArgumentException("Cannot convert '{$value}' to integer");
        }

        return (int) $cleaned;
    }

    private function transformToFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        // Remove common formatting (commas, spaces)
        $cleaned = preg_replace('/[,\s]/', '', (string) $value);
        
        if (!is_numeric($cleaned)) {
            throw new \InvalidArgumentException("Cannot convert '{$value}' to float");
        }

        return (float) $cleaned;
    }

    private function transformToString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return (string) $value;
    }

    // Date transformations
    private function transformDateFormat(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            $date = new \DateTimeImmutable((string) $value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid date format: {$value}");
        }
    }

    private function transformParseDate(mixed $value): \DateTimeImmutable
    {
        if (empty($value)) {
            throw new \InvalidArgumentException("Cannot parse empty date");
        }

        try {
            return new \DateTimeImmutable((string) $value);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid date: {$value}");
        }
    }

    // Utility transformations
    private function transformDefault(mixed $value): mixed
    {
        // This is a placeholder - actual default value comes from FieldMapping
        return $value;
    }

    private function transformCoalesce(mixed $value): mixed
    {
        return $value ?? '';
    }
}
