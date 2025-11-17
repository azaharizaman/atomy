<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class ContractOverlapException extends HrmException
{
    public static function forEmployee(string $employeeId): self
    {
        return new self("Contract overlaps with existing contract for employee '{$employeeId}'.");
    }
}
