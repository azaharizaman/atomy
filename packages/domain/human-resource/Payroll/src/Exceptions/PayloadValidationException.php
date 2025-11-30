<?php

declare(strict_types=1);

namespace Nexus\Payroll\Exceptions;

class PayloadValidationException extends PayrollException
{
    public static function missingEmployeeField(string $field): self
    {
        return new self("Required employee field '{$field}' is missing from payload.");
    }
    
    public static function missingCompanyField(string $field): self
    {
        return new self("Required company field '{$field}' is missing from payload.");
    }
    
    public static function invalidGrossPay(float $grossPay): self
    {
        return new self("Invalid gross pay: {$grossPay}. Must be non-negative.");
    }
}
