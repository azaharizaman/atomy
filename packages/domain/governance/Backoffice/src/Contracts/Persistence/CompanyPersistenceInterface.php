<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts\Persistence;

use Nexus\Backoffice\Contracts\CompanyInterface;

/**
 * Persistence interface for Company write operations (CQRS Command Model).
 *
 * Handles create, update, and delete operations for companies.
 * Follows ISP by focusing solely on persistence operations.
 */
interface CompanyPersistenceInterface
{
    /**
     * Save a new company.
     *
     * @param array<string, mixed> $data Company data
     * @return CompanyInterface Created company
     */
    public function save(array $data): CompanyInterface;

    /**
     * Update an existing company.
     *
     * @param string $id Company identifier
     * @param array<string, mixed> $data Updated company data
     * @return CompanyInterface Updated company
     */
    public function update(string $id, array $data): CompanyInterface;

    /**
     * Delete a company.
     *
     * @param string $id Company identifier
     * @return bool True if deleted successfully
     */
    public function delete(string $id): bool;
}
