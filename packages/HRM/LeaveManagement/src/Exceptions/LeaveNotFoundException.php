<?php

declare(strict_types=1);

namespace Nexus\LeaveManagement\Exceptions;

class LeaveNotFoundException extends LeaveException
{
    public function __construct(string $leaveId)
    {
        parent::__construct("Leave {$leaveId} not found");
    }
}
