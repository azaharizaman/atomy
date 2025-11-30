<?php

declare(strict_types=1);

namespace Nexus\Payroll\Exceptions;

class PayslipNotFoundException extends PayrollException
{
    public static function forId(string $id): self
    {
        return new self("Payslip with ID '{$id}' not found.");
    }
    
    public static function forPayslipNumber(string $tenantId, string $payslipNumber): self
    {
        return new self("Payslip with number '{$payslipNumber}' not found for tenant '{$tenantId}'.");
    }
}
