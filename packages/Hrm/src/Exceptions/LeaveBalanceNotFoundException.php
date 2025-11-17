<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class LeaveBalanceNotFoundException extends HrmException
{
    public static function forEmployeeAndType(string $employeeId, string $leaveTypeId, int $year): self
    {
        return new self("Leave balance not found for employee '{$employeeId}', leave type '{$leaveTypeId}', year {$year}.");
    }
}
