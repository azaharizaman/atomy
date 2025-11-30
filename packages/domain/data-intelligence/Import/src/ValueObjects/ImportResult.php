<?php

declare(strict_types=1);

namespace Nexus\Import\ValueObjects;

/**
 * Immutable import result value object
 * 
 * Contains execution summary and row-level error details.
 */
readonly class ImportResult
{
    /**
     * @param int $successCount Number of successfully imported rows
     * @param int $failedCount Number of failed rows (critical/error severity)
     * @param int $skippedCount Number of skipped rows (warnings)
     * @param array<ImportError> $errors Row-level errors
     */
    public function __construct(
        public int $successCount,
        public int $failedCount,
        public int $skippedCount,
        public array $errors = []
    ) {}

    /**
     * Get total rows processed
     */
    public function getTotalRows(): int
    {
        return $this->successCount + $this->failedCount + $this->skippedCount;
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRate(): float
    {
        $total = $this->getTotalRows();
        if ($total === 0) {
            return 0.0;
        }

        return round(($this->successCount / $total) * 100, 2);
    }

    /**
     * Get error count
     */
    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    /**
     * Get all errors
     */
    public function getAllErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if result has errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if result has critical errors
     */
    public function hasCriticalErrors(): bool
    {
        foreach ($this->errors as $error) {
            if ($error->severity === ErrorSeverity::CRITICAL) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get error count by severity (returns associative array)
     * 
     * @return array<string, int>
     */
    public function getErrorCountBySeverity(): array
    {
        $counts = [
            'WARNING' => 0,
            'ERROR' => 0,
            'CRITICAL' => 0
        ];
        
        foreach ($this->errors as $error) {
            $counts[$error->severity->name]++;
        }
        
        return $counts;
    }

    /**
     * Get errors grouped by field
     * 
     * @return array<string, array<ImportError>>
     */
    public function getErrorsByField(): array
    {
        $grouped = [];
        foreach ($this->errors as $error) {
            if ($error->field !== null) {
                $grouped[$error->field][] = $error;
            }
        }
        return $grouped;
    }

    /**
     * Get errors grouped by row number
     * 
     * @return array<int, array<ImportError>>
     */
    public function getErrorsByRow(): array
    {
        $grouped = [];
        foreach ($this->errors as $error) {
            if ($error->rowNumber !== null) {
                $grouped[$error->rowNumber][] = $error;
            }
        }
        return $grouped;
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'success_count' => $this->successCount,
            'failed_count' => $this->failedCount,
            'skipped_count' => $this->skippedCount,
            'total_rows' => $this->getTotalRows(),
            'success_rate' => $this->getSuccessRate(),
            'error_count' => $this->getErrorCount(),
            'errors' => array_map(fn(ImportError $error) => $error->toArray(), $this->errors),
        ];
    }

    /**
     * Get summary string for logging
     */
    public function getSummary(): string
    {
        return sprintf(
            'Import completed: %d/%d rows successful (%.1f%%), %d failed, %d skipped, %d errors',
            $this->successCount,
            $this->getTotalRows(),
            $this->getSuccessRate(),
            $this->failedCount,
            $this->skippedCount,
            $this->getErrorCount()
        );
    }
}
