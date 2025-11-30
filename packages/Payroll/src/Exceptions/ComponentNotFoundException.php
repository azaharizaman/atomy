<?php

declare(strict_types=1);

namespace Nexus\Payroll\Exceptions;

class ComponentNotFoundException extends PayrollException
{
    public static function forId(string $id): self
    {
        return new self("Component with ID '{$id}' not found.");
    }
    
    public static function forCode(string $tenantId, string $code): self
    {
        return new self("Component with code '{$code}' not found for tenant '{$tenantId}'.");
    }
}
