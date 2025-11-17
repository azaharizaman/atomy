<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class LeaveTypeDuplicateException extends HrmException
{
    public static function forCode(string $code): self
    {
        return new self("Leave type with code '{$code}' already exists.");
    }
}
