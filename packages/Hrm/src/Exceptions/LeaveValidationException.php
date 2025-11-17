<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class LeaveValidationException extends HrmException
{
    public static function invalidStatus(string $status): self
    {
        return new self("Invalid leave status: '{$status}'.");
    }
    
    public static function endDateBeforeStartDate(): self
    {
        return new self("Leave end date cannot be before start date.");
    }
    
    public static function insufficientBalance(float $requested, float $available): self
    {
        return new self("Insufficient leave balance. Requested: {$requested} days, Available: {$available} days.");
    }
    
    public static function cannotModifyApprovedLeave(): self
    {
        return new self("Cannot modify approved leave request.");
    }
    
    public static function missingRequiredField(string $field): self
    {
        return new self("Required field '{$field}' is missing.");
    }
}
