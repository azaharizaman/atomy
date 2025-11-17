<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class LeaveOverlapException extends HrmException
{
    public static function forEmployee(string $employeeId, string $startDate, string $endDate): self
    {
        return new self("Leave request overlaps with existing leave for employee '{$employeeId}' between {$startDate} and {$endDate}.");
    }
}
