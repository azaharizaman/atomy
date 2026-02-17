<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Leave;

use Nexus\Leave\Contracts\LeaveBalanceRepositoryInterface;
use Nexus\Leave\Contracts\LeaveRepositoryInterface;

final readonly class RecalculateLeaveBalanceHandler
{
    public function __construct(
        private LeaveRepositoryInterface $leaveRepository,
        private LeaveBalanceRepositoryInterface $balanceRepository
    ) {}

    public function handle(string $employeeId, string $leaveTypeId, float $allocatedDays): float
    {
        $used = 0.0;
        foreach ($this->leaveRepository->findByEmployeeId($employeeId) as $leave) {
            $status = $leave->status ?? null;
            $typeId = $leave->leaveTypeId ?? null;
            if ($status === 'approved' && $typeId === $leaveTypeId) {
                $used += (float) ($leave->daysRequested ?? 0.0);
            }
        }

        $newBalance = max(0.0, $allocatedDays - $used);

        $balance = $this->balanceRepository->findByEmployeeAndType($employeeId, $leaveTypeId);
        if ($balance !== null && isset($balance->id)) {
            $this->balanceRepository->updateBalance((string) $balance->id, $newBalance);
        }

        return $newBalance;
    }
}
