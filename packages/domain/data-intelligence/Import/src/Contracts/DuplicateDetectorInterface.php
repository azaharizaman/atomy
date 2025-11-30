<?php

declare(strict_types=1);

namespace Nexus\Import\Contracts;

use Nexus\Import\ValueObjects\ImportError;

/**
 * Duplicate detector contract
 * 
 * Detects duplicate records in import data.
 */
interface DuplicateDetectorInterface
{
    /**
     * Detect duplicates within import data
     * 
     * Checks for duplicate rows based on unique key fields.
     * 
     * @param array $rows Array of row data
     * @param array<string> $uniqueKeyFields Field names that form unique key
     * @return array<ImportError> Array of import errors for duplicate rows
     */
    public function detectInternal(
        array $rows,
        array $uniqueKeyFields
    ): array;

    /**
     * Detect duplicate against existing data for a single row
     * 
     * @param array $row Row data
     * @param array<string> $uniqueKeyFields Field names that form unique key
     * @param callable $existsCheck Callback to check if record exists: fn(array $uniqueData): bool
     * @param int $rowNumber Row number for error reporting
     * @return ImportError|null Error if duplicate found, null otherwise
     */
    public function detectExternal(
        array $row,
        array $uniqueKeyFields,
        callable $existsCheck,
        int $rowNumber
    ): ?ImportError;

    /**
     * Check if dataset has duplicates
     * 
     * @param array $rows Array of row data
     * @param array<string> $uniqueKeyFields Field names that form unique key
     * @return bool True if duplicates exist
     */
    public function hasDuplicates(
        array $rows,
        array $uniqueKeyFields
    ): bool;

    /**
     * Reset internal state
     */
    public function reset(): void;
}
