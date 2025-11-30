<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class ContractValidationException extends HrmException
{
    public static function invalidContractType(string $type): self
    {
        return new self("Invalid contract type: '{$type}'.");
    }
    
    public static function endDateRequired(string $contractType): self
    {
        return new self("End date is required for contract type '{$contractType}'.");
    }
    
    public static function endDateBeforeStartDate(): self
    {
        return new self("Contract end date cannot be before start date.");
    }
    
    public static function negativeSalary(): self
    {
        return new self("Contract basic salary cannot be negative.");
    }
    
    public static function missingRequiredField(string $field): self
    {
        return new self("Required field '{$field}' is missing.");
    }
}
