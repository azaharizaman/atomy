<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class LeaveTypeNotFoundException extends HrmException
{
    public static function forId(string $id): self
    {
        return new self("Leave type with ID '{$id}' not found.");
    }
    
    public static function forCode(string $tenantId, string $code): self
    {
        return new self("Leave type with code '{$code}' not found for tenant '{$tenantId}'.");
    }
}
