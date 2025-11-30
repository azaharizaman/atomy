<?php

declare(strict_types=1);

namespace Nexus\Import\Core\Engine;

use Nexus\Import\ValueObjects\ImportError;
use Nexus\Import\ValueObjects\ErrorSeverity;

/**
 * Error collector
 * 
 * Aggregates import errors from multiple sources (transformation, mapping, validation).
 */
final class ErrorCollector
{
    /**
     * @var array<int, ImportError[]>
     */
    private array $errorsByRow = [];

    /**
     * @var ImportError[]
     */
    private array $globalErrors = [];

    public function addError(ImportError $error): void
    {
        if ($error->rowNumber === null) {
            $this->globalErrors[] = $error;
        } else {
            $this->errorsByRow[$error->rowNumber][] = $error;
        }
    }

    /**
     * @param ImportError[] $errors
     */
    public function addErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $this->addError($error);
        }
    }

    public function addGlobalError(string $message, ErrorSeverity $severity = ErrorSeverity::ERROR): void
    {
        $this->globalErrors[] = new ImportError(
            rowNumber: null,
            field: null,
            severity: $severity,
            message: $message
        );
    }

    /**
     * Get all errors for a specific row
     * 
     * @return ImportError[]
     */
    public function getErrorsForRow(int $rowNumber): array
    {
        return $this->errorsByRow[$rowNumber] ?? [];
    }

    /**
     * Get all errors
     * 
     * @return ImportError[]
     */
    public function getAllErrors(): array
    {
        $all = $this->globalErrors;

        foreach ($this->errorsByRow as $errors) {
            $all = array_merge($all, $errors);
        }

        return $all;
    }

    /**
     * Get errors grouped by severity
     * 
     * @return array<string, ImportError[]>
     */
    public function getErrorsBySeverity(): array
    {
        $grouped = [
            'WARNING' => [],
            'ERROR' => [],
            'CRITICAL' => []
        ];

        foreach ($this->getAllErrors() as $error) {
            $grouped[$error->severity->name][] = $error;
        }

        return $grouped;
    }

    /**
     * Get count of errors by severity
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

        foreach ($this->getAllErrors() as $error) {
            $counts[$error->severity->name]++;
        }

        return $counts;
    }

    public function hasErrors(): bool
    {
        return !empty($this->globalErrors) || !empty($this->errorsByRow);
    }

    public function hasCriticalErrors(): bool
    {
        foreach ($this->getAllErrors() as $error) {
            if ($error->severity === ErrorSeverity::CRITICAL) {
                return true;
            }
        }

        return false;
    }

    public function getErrorCount(): int
    {
        return count($this->getAllErrors());
    }

    public function getRowsWithErrors(): array
    {
        return array_keys($this->errorsByRow);
    }

    public function clear(): void
    {
        $this->errorsByRow = [];
        $this->globalErrors = [];
    }

    /**
     * Filter rows that have critical errors
     * 
     * @return int[] Row numbers with critical errors
     */
    public function getRowsWithCriticalErrors(): array
    {
        $rows = [];

        foreach ($this->errorsByRow as $rowNumber => $errors) {
            foreach ($errors as $error) {
                if ($error->severity === ErrorSeverity::CRITICAL) {
                    $rows[] = $rowNumber;
                    break;
                }
            }
        }

        return $rows;
    }
}
