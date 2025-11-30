<?php

declare(strict_types=1);

namespace Nexus\Payroll\Exceptions;

class PayslipValidationException extends PayrollException
{
    public static function cannotModifyApproved(): self
    {
        return new self("Cannot modify approved payslip.");
    }
    
    public static function cannotModifyPaid(): self
    {
        return new self("Cannot modify paid payslip.");
    }
    
    public static function invalidPeriod(): self
    {
        return new self("Period end date must be after period start date.");
    }
}
