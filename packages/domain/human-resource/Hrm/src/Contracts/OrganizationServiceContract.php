<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

/**
 * Contract for integrating with Nexus\Backoffice organizational structure.
 * 
 * This interface must be implemented by the consuming application to provide
 * organizational context for HRM operations.
 */
interface OrganizationServiceContract
{
    /**
     * Get employee's manager ID from organizational structure.
     *
     * @param string $employeeId Employee ULID
     * @return string|null Manager's employee ULID
     */
    public function getEmployeeManager(string $employeeId): ?string;
    
    /**
     * Get employee's department from organizational structure.
     *
     * @param string $employeeId Employee ULID
     * @return array{id: string, name: string}|null
     */
    public function getEmployeeDepartment(string $employeeId): ?array;
    
    /**
     * Get employee's office from organizational structure.
     *
     * @param string $employeeId Employee ULID
     * @return array{id: string, name: string}|null
     */
    public function getEmployeeOffice(string $employeeId): ?array;
    
    /**
     * Get direct reports for a manager.
     *
     * @param string $managerId Manager's employee ULID
     * @return array<string> Array of employee ULIDs
     */
    public function getDirectReports(string $managerId): array;
    
    /**
     * Verify if employee is a manager.
     *
     * @param string $employeeId Employee ULID
     * @return bool
     */
    public function isManager(string $employeeId): bool;
}
