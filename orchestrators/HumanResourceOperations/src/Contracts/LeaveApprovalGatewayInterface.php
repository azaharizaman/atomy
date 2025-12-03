<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Contracts;

interface LeaveApprovalGatewayInterface
{
    public function approveLeave(string $leaveId, string $approverId): void;
    
    public function rejectLeave(string $leaveId, string $approverId, string $reason): void;
}
