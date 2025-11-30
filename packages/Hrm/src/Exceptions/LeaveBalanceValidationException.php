<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class LeaveBalanceValidationException extends HrmException
{
    public static function negativeBalance(): self
    {
        return new self("Leave balance cannot be negative.");
    }
    
    public static function invalidYear(int $year): self
    {
        return new self("Invalid year: {$year}.");
    }
}
