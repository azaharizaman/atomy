<?php

declare(strict_types=1);

namespace Nexus\LeaveManagement\Contracts;

interface AccrualStrategyInterface
{
    public function calculate(string $employeeId, string $leaveTypeId, \DateTimeImmutable $asOfDate): float;
    
    public function getName(): string;
}
