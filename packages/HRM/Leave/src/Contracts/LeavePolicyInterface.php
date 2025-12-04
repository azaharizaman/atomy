<?php

declare(strict_types=1);

namespace Nexus\Leave\Contracts;

interface LeavePolicyInterface
{
    public function canApplyLeave(string $employeeId, string $leaveTypeId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): bool;
    
    public function validateLeaveRequest(array $leaveData): array;
}
