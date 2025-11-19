<?php

declare(strict_types=1);

namespace Nexus\Import\Contracts;

use Nexus\Import\ValueObjects\ImportMode;
use Nexus\Import\ValueObjects\ImportResult;

/**
 * Import handler contract
 * 
 * Domain packages implement this to handle persistence of imported data.
 * This is where the actual "save to database" happens.
 */
interface ImportHandlerInterface
{
        /**
     * Handle import row with specified mode
     * 
     * Persists the row data according to the import mode.
     * 
     * @param array<string, mixed> $data Mapped row data
     * @param ImportMode $mode Import mode
     * @throws \Exception If persistence fails
     */
    public function handle(
        array $data,
        ImportMode $mode
    ): void;

    /**
     * Get unique key field names for duplicate detection
     * 
     * Returns the field names that uniquely identify a record.
     * Used for UPDATE, UPSERT, and DELETE modes.
     * 
     * @return array<string> Field names (e.g., ['email'], ['sku'], ['tenant_id', 'code'])
     */
    public function getUniqueKeyFields(): array;

    /**
     * Get required field names
     * 
     * @return array<string> Required field names
     */
    public function getRequiredFields(): array;

    /**
     * Check if handler supports the given import mode
     */
    public function supportsMode(ImportMode $mode): bool;

    /**
     * Check if record exists with unique data
     * 
     * @param array<string, mixed> $uniqueData Data for unique fields
     * @return bool True if record exists
     */
    public function exists(array $uniqueData): bool;

    /**
     * Validate data against domain rules
     * 
     * @param array<string, mixed> $data Row data to validate
     * @return array<string> Error messages (empty if valid)
     */
    public function validateData(array $data): array;
}
