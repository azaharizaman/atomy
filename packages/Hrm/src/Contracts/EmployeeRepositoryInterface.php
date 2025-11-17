<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

/**
 * Repository contract for employee persistence operations.
 */
interface EmployeeRepositoryInterface
{
    /**
     * Find employee by ID.
     *
     * @param string $id Employee ULID
     * @return EmployeeInterface|null
     */
    public function findById(string $id): ?EmployeeInterface;
    
    /**
     * Find employee by employee code.
     *
     * @param string $tenantId Tenant ULID
     * @param string $employeeCode Unique employee code
     * @return EmployeeInterface|null
     */
    public function findByEmployeeCode(string $tenantId, string $employeeCode): ?EmployeeInterface;
    
    /**
     * Find employee by email.
     *
     * @param string $tenantId Tenant ULID
     * @param string $email Employee email
     * @return EmployeeInterface|null
     */
    public function findByEmail(string $tenantId, string $email): ?EmployeeInterface;
    
    /**
     * Get all employees for a tenant with optional filters.
     *
     * @param string $tenantId Tenant ULID
     * @param array<string, mixed> $filters
     * @return array<EmployeeInterface>
     */
    public function getAll(string $tenantId, array $filters = []): array;
    
    /**
     * Get employees by manager ID.
     *
     * @param string $managerId Manager's employee ULID
     * @return array<EmployeeInterface>
     */
    public function getDirectReports(string $managerId): array;
    
    /**
     * Get employees by department ID.
     *
     * @param string $departmentId Department ULID from Backoffice
     * @return array<EmployeeInterface>
     */
    public function getByDepartment(string $departmentId): array;
    
    /**
     * Create a new employee.
     *
     * @param array<string, mixed> $data
     * @return EmployeeInterface
     * @throws \Nexus\Hrm\Exceptions\EmployeeDuplicateException
     * @throws \Nexus\Hrm\Exceptions\EmployeeValidationException
     */
    public function create(array $data): EmployeeInterface;
    
    /**
     * Update an employee.
     *
     * @param string $id Employee ULID
     * @param array<string, mixed> $data
     * @return EmployeeInterface
     * @throws \Nexus\Hrm\Exceptions\EmployeeNotFoundException
     * @throws \Nexus\Hrm\Exceptions\EmployeeValidationException
     */
    public function update(string $id, array $data): EmployeeInterface;
    
    /**
     * Delete an employee (soft delete).
     *
     * @param string $id Employee ULID
     * @return bool
     * @throws \Nexus\Hrm\Exceptions\EmployeeNotFoundException
     */
    public function delete(string $id): bool;
    
    /**
     * Check if employee code exists for tenant.
     *
     * @param string $tenantId Tenant ULID
     * @param string $employeeCode Employee code to check
     * @param string|null $excludeId Employee ID to exclude from check
     * @return bool
     */
    public function employeeCodeExists(string $tenantId, string $employeeCode, ?string $excludeId = null): bool;
}
