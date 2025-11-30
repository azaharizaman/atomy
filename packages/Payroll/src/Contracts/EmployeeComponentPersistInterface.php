<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

/**
 * Persistence contract for employee component write operations.
 *
 * Implements CQRS pattern - write operations only.
 */
interface EmployeeComponentPersistInterface
{
    /**
     * Create a new employee component assignment.
     *
     * @param array<string, mixed> $data Employee component data
     * @return EmployeeComponentInterface Created employee component
     */
    public function create(array $data): EmployeeComponentInterface;

    /**
     * Update an existing employee component assignment.
     *
     * @param string $id Employee component ULID
     * @param array<string, mixed> $data Updated data
     * @return EmployeeComponentInterface Updated employee component
     */
    public function update(string $id, array $data): EmployeeComponentInterface;

    /**
     * Delete an employee component assignment.
     *
     * @param string $id Employee component ULID
     * @return bool True if deleted successfully
     */
    public function delete(string $id): bool;
}
