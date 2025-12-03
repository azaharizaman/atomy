<?php

declare(strict_types=1);

namespace Nexus\LeaveManagement\Services;

use Nexus\LeaveManagement\Contracts\LeaveCalculatorInterface;
use Nexus\LeaveManagement\Contracts\LeaveBalanceRepositoryInterface;

final readonly class LeaveBalanceCalculator implements LeaveCalculatorInterface
{
    public function __construct(
        private LeaveBalanceRepositoryInterface $balanceRepository
    ) {}

    public function calculateBalance(string $employeeId, string $leaveTypeId): float
    {
        // TODO: Implement balance calculation logic
        return 0.0;
    }

    public function calculateAccrual(string $employeeId, string $leaveTypeId, \DateTimeImmutable $asOfDate): float
    {
        // TODO: Implement accrual calculation logic
        return 0.0;
    }
}
