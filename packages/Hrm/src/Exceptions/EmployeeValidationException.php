<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class EmployeeValidationException extends HrmException
{
    public static function invalidStatus(string $status): self
    {
        return new self("Invalid employee status: '{$status}'.");
    }
    
    public static function invalidEmploymentType(string $type): self
    {
        return new self("Invalid employment type: '{$type}'.");
    }
    
    public static function invalidDateOfBirth(): self
    {
        return new self("Date of birth cannot be in the future.");
    }
    
    public static function invalidHireDate(): self
    {
        return new self("Hire date cannot be before date of birth.");
    }
    
    public static function missingRequiredField(string $field): self
    {
        return new self("Required field '{$field}' is missing.");
    }
}
