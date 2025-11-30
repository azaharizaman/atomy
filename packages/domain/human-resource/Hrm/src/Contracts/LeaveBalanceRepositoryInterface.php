<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

/**
 * Repository contract for leave balance persistence operations.
 */
interface LeaveBalanceRepositoryInterface
{
    /**
     * Get leave balance for employee, leave type, and year.
     *
     * @param string $employeeId Employee ULID
     * @param string $leaveTypeId Leave type ULID
     * @param int $year Calendar year
     * @return LeaveBalanceInterface|null
     */
    public function getBalance(string $employeeId, string $leaveTypeId, int $year): ?LeaveBalanceInterface;
    
    /**
     * Get all leave balances for employee in year.
     *
     * @param string $employeeId Employee ULID
     * @param int $year Calendar year
     * @return array<LeaveBalanceInterface>
     */
    public function getEmployeeBalances(string $employeeId, int $year): array;
    
    /**
     * Create or update leave balance.
     *
     * @param array<string, mixed> $data
     * @return LeaveBalanceInterface
     * @throws \Nexus\Hrm\Exceptions\LeaveBalanceValidationException
     */
    public function createOrUpdate(array $data): LeaveBalanceInterface;
    
    /**
     * Adjust leave balance by days.
     *
     * @param string $employeeId Employee ULID
     * @param string $leaveTypeId Leave type ULID
     * @param int $year Calendar year
     * @param float $days Days to adjust (positive or negative)
     * @return LeaveBalanceInterface
     * @throws \Nexus\Hrm\Exceptions\LeaveBalanceNotFoundException
     */
    public function adjustBalance(string $employeeId, string $leaveTypeId, int $year, float $days): LeaveBalanceInterface;
    
    /**
     * Carry forward remaining days to next year.
     *
     * @param string $employeeId Employee ULID
     * @param string $leaveTypeId Leave type ULID
     * @param int $fromYear Source year
     * @param int $toYear Target year
     * @param float $maxCarryForwardDays Maximum days to carry forward
     * @return LeaveBalanceInterface
     */
    public function carryForward(
        string $employeeId,
        string $leaveTypeId,
        int $fromYear,
        int $toYear,
        float $maxCarryForwardDays
    ): LeaveBalanceInterface;
}
