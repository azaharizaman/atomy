<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

/**
 * Query contract for employee component read operations.
 *
 * Implements CQRS pattern - read operations only.
 */
interface EmployeeComponentQueryInterface
{
    /**
     * Find an employee component by its ID.
     *
     * @param string $id Employee component ULID
     * @return EmployeeComponentInterface|null
     */
    public function findById(string $id): ?EmployeeComponentInterface;

    /**
     * Get all active components assigned to an employee.
     *
     * @param string $employeeId Employee ULID
     * @return array<EmployeeComponentInterface>
     */
    public function getActiveComponentsForEmployee(string $employeeId): array;
}
