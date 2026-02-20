<?php

declare(strict_types=1);

namespace Nexus\Leave\Exceptions;

class InsufficientBalanceException extends LeaveException
{
    public function __construct(string $employeeId, string $leaveType, float $requested, float $available)
    {
        parent::__construct(
            "Insufficient balance for employee {$employeeId}, leave type {$leaveType}. " .
            "Requested: {$requested}, Available: {$available}"
        );
    }
}
