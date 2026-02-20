<?php

declare(strict_types=1);

namespace Nexus\PayrollCore\Contracts;

interface PayslipRepositoryInterface
{
    public function findById(string $id): ?object;
    
    public function findByEmployeeAndPeriod(string $employeeId, string $periodId): ?object;
    
    public function save(object $payslip): string;
}
