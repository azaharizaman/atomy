<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class EmployeeNotFoundException extends HrmException
{
    public static function forId(string $id): self
    {
        return new self("Employee with ID '{$id}' not found.");
    }
    
    public static function forEmployeeCode(string $tenantId, string $employeeCode): self
    {
        return new self("Employee with code '{$employeeCode}' not found for tenant '{$tenantId}'.");
    }
    
    public static function forEmail(string $tenantId, string $email): self
    {
        return new self("Employee with email '{$email}' not found for tenant '{$tenantId}'.");
    }
}
