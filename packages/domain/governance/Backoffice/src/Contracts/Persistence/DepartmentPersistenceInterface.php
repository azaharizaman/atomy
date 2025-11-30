<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts\Persistence;

use Nexus\Backoffice\Contracts\DepartmentInterface;

/**
 * Persistence interface for Department write operations (CQRS Command Model).
 *
 * Handles create, update, and delete operations for departments.
 * Follows ISP by focusing solely on persistence operations.
 */
interface DepartmentPersistenceInterface
{
    /**
     * Save a new department.
     *
     * @param array<string, mixed> $data Department data
     * @return DepartmentInterface Created department
     */
    public function save(array $data): DepartmentInterface;

    /**
     * Update an existing department.
     *
     * @param string $id Department identifier
     * @param array<string, mixed> $data Updated department data
     * @return DepartmentInterface Updated department
     */
    public function update(string $id, array $data): DepartmentInterface;

    /**
     * Delete a department.
     *
     * @param string $id Department identifier
     * @return bool True if deleted successfully
     */
    public function delete(string $id): bool;
}
