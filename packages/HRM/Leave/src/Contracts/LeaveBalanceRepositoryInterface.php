<?php

declare(strict_types=1);

namespace Nexus\Leave\Contracts;

interface LeaveBalanceRepositoryInterface
{
    public function findByEmployeeAndType(string $employeeId, string $leaveTypeId): ?object;
    
    public function save(object $balance): string;
    
    public function updateBalance(string $balanceId, float $amount): void;
}
