<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Leave;

use Nexus\Leave\Contracts\LeaveBalanceRepositoryInterface;

final readonly class ProcessCarryForwardHandler
{
    public function __construct(
        private LeaveBalanceRepositoryInterface $balanceRepository
    ) {}

    public function handle(string $employeeId, string $leaveTypeId, float $unusedDays, float $maxCarryForward): float
    {
        $carryForward = min($unusedDays, $maxCarryForward);

        $balance = $this->balanceRepository->findByEmployeeAndType($employeeId, $leaveTypeId);
        if ($balance !== null && isset($balance->id, $balance->balance)) {
            $newBalance = (float) $balance->balance + $carryForward;
            $this->balanceRepository->updateBalance((string) $balance->id, $newBalance);
            return $newBalance;
        }

        return $carryForward;
    }
}
