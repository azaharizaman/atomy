<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

use DateTimeInterface;

/**
 * Repository contract for payslip persistence.
 */
interface PayslipRepositoryInterface
{
    public function findById(string $id): ?PayslipInterface;
    
    public function findByPayslipNumber(string $tenantId, string $payslipNumber): ?PayslipInterface;
    
    public function getEmployeePayslips(string $employeeId, ?int $year = null): array;
    
    public function getPayslipsForPeriod(
        string $tenantId,
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd
    ): array;
    
    public function create(array $data): PayslipInterface;
    
    public function update(string $id, array $data): PayslipInterface;
    
    public function delete(string $id): bool;
}
