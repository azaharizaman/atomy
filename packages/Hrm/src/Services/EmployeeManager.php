<?php

declare(strict_types=1);

namespace Nexus\Hrm\Services;

use DateTimeInterface;
use Nexus\Hrm\Contracts\EmployeeInterface;
use Nexus\Hrm\Contracts\EmployeeRepositoryInterface;
use Nexus\Hrm\Contracts\OrganizationServiceContract;
use Nexus\Hrm\Exceptions\EmployeeNotFoundException;
use Nexus\Hrm\Exceptions\EmployeeValidationException;
use Nexus\Hrm\ValueObjects\EmployeeStatus;

/**
 * Service for managing employee lifecycle and operations.
 */
readonly class EmployeeManager
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private OrganizationServiceContract $organizationService,
    ) {
    }
    
    /**
     * Create a new employee.
     *
     * @param array<string, mixed> $data
     * @return EmployeeInterface
     * @throws \Nexus\Hrm\Exceptions\EmployeeDuplicateException
     * @throws EmployeeValidationException
     */
    public function createEmployee(array $data): EmployeeInterface
    {
        $this->validateEmployeeData($data);
        
        // Set default status to probationary
        $data['status'] ??= EmployeeStatus::PROBATIONARY->value;
        
        return $this->employeeRepository->create($data);
    }
    
    /**
     * Update an employee.
     *
     * @param string $id Employee ULID
     * @param array<string, mixed> $data
     * @return EmployeeInterface
     * @throws EmployeeNotFoundException
     * @throws EmployeeValidationException
     */
    public function updateEmployee(string $id, array $data): EmployeeInterface
    {
        $employee = $this->getEmployeeById($id);
        
        $this->validateEmployeeData($data, $id);
        
        return $this->employeeRepository->update($id, $data);
    }
    
    /**
     * Confirm an employee (end probation).
     *
     * @param string $id Employee ULID
     * @param string $confirmationDate Confirmation date (Y-m-d)
     * @return EmployeeInterface
     * @throws EmployeeNotFoundException
     */
    public function confirmEmployee(string $id, string $confirmationDate): EmployeeInterface
    {
        $employee = $this->getEmployeeById($id);
        
        if (!$employee->isProbationary()) {
            throw new EmployeeValidationException("Employee is not in probationary status.");
        }
        
        return $this->employeeRepository->update($id, [
            'status' => EmployeeStatus::CONFIRMED->value,
            'confirmation_date' => $confirmationDate,
        ]);
    }
    
    /**
     * Terminate an employee.
     *
     * @param string $id Employee ULID
     * @param string $terminationDate Termination date (Y-m-d)
     * @param string $reason Termination reason
     * @return EmployeeInterface
     * @throws EmployeeNotFoundException
     */
    public function terminateEmployee(string $id, string $terminationDate, string $reason): EmployeeInterface
    {
        $employee = $this->getEmployeeById($id);
        
        if ($employee->isTerminated()) {
            throw new EmployeeValidationException("Employee is already terminated.");
        }
        
        return $this->employeeRepository->update($id, [
            'status' => EmployeeStatus::TERMINATED->value,
            'termination_date' => $terminationDate,
            'metadata' => array_merge($employee->getMetadata(), [
                'termination_reason' => $reason,
            ]),
        ]);
    }
    
    /**
     * Get employee by ID.
     *
     * @param string $id Employee ULID
     * @return EmployeeInterface
     * @throws EmployeeNotFoundException
     */
    public function getEmployeeById(string $id): EmployeeInterface
    {
        $employee = $this->employeeRepository->findById($id);
        
        if (!$employee) {
            throw EmployeeNotFoundException::forId($id);
        }
        
        return $employee;
    }
    
    /**
     * Get employee by employee code.
     *
     * @param string $tenantId Tenant ULID
     * @param string $employeeCode Employee code
     * @return EmployeeInterface
     * @throws EmployeeNotFoundException
     */
    public function getEmployeeByCode(string $tenantId, string $employeeCode): EmployeeInterface
    {
        $employee = $this->employeeRepository->findByEmployeeCode($tenantId, $employeeCode);
        
        if (!$employee) {
            throw EmployeeNotFoundException::forEmployeeCode($tenantId, $employeeCode);
        }
        
        return $employee;
    }
    
    /**
     * Get all employees for tenant with optional filters.
     *
     * @param string $tenantId Tenant ULID
     * @param array<string, mixed> $filters
     * @return array<EmployeeInterface>
     */
    public function getAllEmployees(string $tenantId, array $filters = []): array
    {
        return $this->employeeRepository->getAll($tenantId, $filters);
    }
    
    /**
     * Get direct reports for a manager.
     *
     * @param string $managerId Manager's employee ULID
     * @return array<EmployeeInterface>
     */
    public function getDirectReports(string $managerId): array
    {
        return $this->employeeRepository->getDirectReports($managerId);
    }
    
    /**
     * Get employee's manager from organizational structure.
     *
     * @param string $employeeId Employee ULID
     * @return EmployeeInterface|null
     */
    public function getEmployeeManager(string $employeeId): ?EmployeeInterface
    {
        $managerId = $this->organizationService->getEmployeeManager($employeeId);
        
        if (!$managerId) {
            return null;
        }
        
        try {
            return $this->getEmployeeById($managerId);
        } catch (EmployeeNotFoundException) {
            return null;
        }
    }
    
    /**
     * Delete an employee (soft delete).
     *
     * @param string $id Employee ULID
     * @return bool
     * @throws EmployeeNotFoundException
     */
    public function deleteEmployee(string $id): bool
    {
        $employee = $this->getEmployeeById($id);
        
        return $this->employeeRepository->delete($id);
    }
    
    /**
     * Validate employee data.
     *
     * @param array<string, mixed> $data
     * @param string|null $excludeId Employee ID to exclude from unique checks
     * @throws EmployeeValidationException
     */
    private function validateEmployeeData(array $data, ?string $excludeId = null): void
    {
        // Validate status if provided
        if (isset($data['status'])) {
            try {
                EmployeeStatus::from($data['status']);
            } catch (\ValueError) {
                throw EmployeeValidationException::invalidStatus($data['status']);
            }
        }
    }
}
