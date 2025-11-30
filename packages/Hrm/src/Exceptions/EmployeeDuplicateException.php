<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class EmployeeDuplicateException extends HrmException
{
    public static function forEmployeeCode(string $employeeCode): self
    {
        return new self("Employee code '{$employeeCode}' already exists.");
    }
    
    public static function forEmail(string $email): self
    {
        return new self("Employee email '{$email}' already exists.");
    }
}
