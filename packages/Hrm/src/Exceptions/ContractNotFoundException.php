<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class ContractNotFoundException extends HrmException
{
    public static function forId(string $id): self
    {
        return new self("Contract with ID '{$id}' not found.");
    }
    
    public static function noActiveContract(string $employeeId): self
    {
        return new self("No active contract found for employee '{$employeeId}'.");
    }
}
