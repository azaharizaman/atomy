<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class LeaveTypeValidationException extends HrmException
{
    public static function missingRequiredField(string $field): self
    {
        return new self("Required field '{$field}' is missing.");
    }
    
    public static function negativeDays(string $field): self
    {
        return new self("Field '{$field}' cannot be negative.");
    }
}
