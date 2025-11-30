<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

use DateTimeInterface;

/**
 * Query contract for payslip read operations.
 *
 * Implements CQRS pattern - read operations only.
 */
interface PayslipQueryInterface
{
    /**
     * Find a payslip by its ID.
     *
     * @param string $id Payslip ULID
     * @return PayslipInterface|null
     */
    public function findById(string $id): ?PayslipInterface;

    /**
     * Find a payslip by its number within a tenant.
     *
     * @param string $tenantId Tenant ULID
     * @param string $payslipNumber Payslip number
     * @return PayslipInterface|null
     */
    public function findByPayslipNumber(string $tenantId, string $payslipNumber): ?PayslipInterface;

    /**
     * Get all payslips for an employee.
     *
     * @param string $employeeId Employee ULID
     * @param int|null $year Optional filter by year
     * @return array<PayslipInterface>
     */
    public function getEmployeePayslips(string $employeeId, ?int $year = null): array;

    /**
     * Get all payslips for a specific period.
     *
     * @param string $tenantId Tenant ULID
     * @param DateTimeInterface $periodStart Period start date
     * @param DateTimeInterface $periodEnd Period end date
     * @return array<PayslipInterface>
     */
    public function getPayslipsForPeriod(
        string $tenantId,
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd
    ): array;
}
