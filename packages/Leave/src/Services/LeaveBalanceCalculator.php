<?php

declare(strict_types=1);

namespace Nexus\Leave\Services;

use Nexus\Leave\Contracts\LeaveCalculatorInterface;
use Nexus\Leave\Contracts\LeaveBalanceRepositoryInterface;

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
