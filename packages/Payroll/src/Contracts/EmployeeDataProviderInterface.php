<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

use DateTimeInterface;

/**
 * Interface for fetching employee data from HRM system.
 * 
 * This interface abstracts the retrieval of employee information
 * required for payroll processing. Implementations can connect to
 * various HRM systems or use different data sources.
 */
interface EmployeeDataProviderInterface
{
    /**
     * Get all active employees for a tenant.
     *
     * @param string $tenantId Tenant ULID
     * @param DateTimeInterface $effectiveDate Date for checking employment status
     * @return array<EmployeeData> Active employees
     */
    public function getActiveEmployees(string $tenantId, DateTimeInterface $effectiveDate): array;

    /**
     * Get specific employees by their IDs.
     *
     * @param string $tenantId Tenant ULID
     * @param array<string> $employeeIds List of employee ULIDs
     * @return array<EmployeeData> Employee data for the requested IDs
     */
    public function getEmployeesByIds(string $tenantId, array $employeeIds): array;

    /**
     * Get employees filtered by department.
     *
     * @param string $tenantId Tenant ULID
     * @param string $departmentId Department ULID
     * @param DateTimeInterface $effectiveDate Date for checking employment status
     * @return array<EmployeeData> Active employees in the department
     */
    public function getEmployeesByDepartment(
        string $tenantId, 
        string $departmentId, 
        DateTimeInterface $effectiveDate
    ): array;

    /**
     * Get employee year-to-date payroll summary.
     *
     * @param string $employeeId Employee ULID
     * @param int $year Year for YTD calculation
     * @return YtdPayrollSummary Year-to-date payroll data
     */
    public function getEmployeeYtdPayroll(string $employeeId, int $year): YtdPayrollSummary;
}

/**
 * Value object containing employee data required for payroll processing.
 */
final readonly class EmployeeData
{
    public function __construct(
        public string $employeeId,
        public string $employeeNumber,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $taxId,
        public string $citizenship,
        public string $employmentType, // permanent, contract, part-time
        public string $payGroupId,
        public string $bankAccountId,
        public string $bankName,
        public string $bankAccountNumber,
        public string $bankAccountName,
        public DateTimeInterface $hireDate,
        public ?DateTimeInterface $terminationDate,
        public array $metadata, // Additional employee-specific data
    ) {}

    /**
     * Get the employee's full name.
     */
    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    /**
     * Check if employee is active as of a given date.
     */
    public function isActiveOn(DateTimeInterface $date): bool
    {
        if ($this->terminationDate !== null && $date > $this->terminationDate) {
            return false;
        }
        return $date >= $this->hireDate;
    }
}

/**
 * Year-to-date payroll summary for an employee.
 */
final readonly class YtdPayrollSummary
{
    public function __construct(
        public string $employeeId,
        public int $year,
        public float $ytdGrossPay,
        public float $ytdTaxPaid,
        public float $ytdEpfContributions,
        public float $ytdSocsoContributions,
        public float $ytdEisContributions,
    ) {}
}
