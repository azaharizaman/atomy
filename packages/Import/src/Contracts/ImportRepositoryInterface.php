<?php

declare(strict_types=1);

namespace Nexus\Import\Contracts;

use Nexus\Import\ValueObjects\ImportRecord;

/**
 * Repository for persisting Import records
 */
interface ImportRepositoryInterface
{
    /**
     * Save a new import record
     */
    public function save(ImportRecord $record): void;

    /**
     * Update an existing import record
     */
    public function update(ImportRecord $record): void;

    /**
     * Find an import by ID
     */
    public function findById(string $id): ?ImportRecord;

    /**
     * Find imports by tenant
     */
    public function findByTenant(string $tenantId, int $limit = 50): array;

    /**
     * Find pending imports (for queue processing)
     */
    public function findPending(int $limit = 10): array;

    /**
     * Delete an import
     */
    public function delete(string $id): void;
}
