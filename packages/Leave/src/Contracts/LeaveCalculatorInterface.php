<?php

declare(strict_types=1);

namespace Nexus\Leave\Contracts;

interface LeaveCalculatorInterface
{
    public function calculateBalance(string $employeeId, string $leaveTypeId): float;
    
    public function calculateAccrual(string $employeeId, string $leaveTypeId, \DateTimeImmutable $asOfDate): float;
}
