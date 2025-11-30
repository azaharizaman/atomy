<?php

declare(strict_types=1);

namespace Nexus\Hrm\Exceptions;

class LeaveNotFoundException extends HrmException
{
    public static function forId(string $id): self
    {
        return new self("Leave request with ID '{$id}' not found.");
    }
}
