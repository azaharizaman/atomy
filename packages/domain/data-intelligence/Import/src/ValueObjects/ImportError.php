<?php

declare(strict_types=1);

namespace Nexus\Import\ValueObjects;

/**
 * Immutable import error value object
 * 
 * Represents a row-level error encountered during import.
 */
readonly class ImportError
{
    /**
     * @param int|null $rowNumber Row number (1-indexed, null for global errors)
     * @param string|null $field Field name that caused the error (null for global errors)
     * @param ErrorSeverity $severity Error severity level
     * @param string $message Human-readable error message
     * @param string $code Error code for programmatic handling
     * @param array $context Additional context data
     */
    public function __construct(
        public ?int $rowNumber,
        public ?string $field,
        public ErrorSeverity $severity,
        public string $message,
        public string $code = 'UNKNOWN_ERROR',
        public array $context = []
    ) {}

    /**
     * Check if error is critical
     */
    public function isCritical(): bool
    {
        return $this->severity === ErrorSeverity::CRITICAL;
    }

    /**
     * Check if error should skip the row
     */
    public function shouldSkipRow(): bool
    {
        return $this->severity->shouldSkipRow();
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'row_number' => $this->rowNumber,
            'field' => $this->field,
            'severity' => $this->severity->value,
            'message' => $this->message,
            'code' => $this->code,
            'context' => $this->context,
        ];
    }

    /**
     * Convert to string for logging
     */
    public function toString(): string
    {
        $parts = [
            "Row {$this->rowNumber}",
            "Field '{$this->field}'",
            "[{$this->severity->value}]",
            "[{$this->code}]",
            $this->message
        ];

        return implode(' - ', $parts);
    }
}
