<?php

declare(strict_types=1);

namespace Nexus\Hrm\Services;

use DateTimeInterface;
use Nexus\Hrm\Contracts\LeaveInterface;
use Nexus\Hrm\Contracts\LeaveRepositoryInterface;
use Nexus\Hrm\Contracts\LeaveTypeRepositoryInterface;
use Nexus\Hrm\Contracts\LeaveBalanceRepositoryInterface;
use Nexus\Hrm\Exceptions\LeaveNotFoundException;
use Nexus\Hrm\Exceptions\LeaveTypeNotFoundException;
use Nexus\Hrm\Exceptions\LeaveValidationException;
use Nexus\Hrm\ValueObjects\LeaveStatus;

/**
 * Service for managing leave requests and balances.
 */
readonly class LeaveManager
{
    public function __construct(
        private LeaveRepositoryInterface $leaveRepository,
        private LeaveTypeRepositoryInterface $leaveTypeRepository,
        private LeaveBalanceRepositoryInterface $leaveBalanceRepository,
    ) {
    }
    
    /**
     * Create a leave request.
     *
     * @param array<string, mixed> $data
     * @return LeaveInterface
     * @throws \Nexus\Hrm\Exceptions\LeaveOverlapException
     * @throws LeaveValidationException
     * @throws LeaveTypeNotFoundException
     */
    public function createLeaveRequest(array $data): LeaveInterface
    {
        $this->validateLeaveData($data);
        
        // Verify leave type exists
        $leaveType = $this->leaveTypeRepository->findById($data['leave_type_id']);
        if (!$leaveType) {
            throw LeaveTypeNotFoundException::forId($data['leave_type_id']);
        }
        
        // Check leave balance if leave type requires it
        if (!$leaveType->isUnpaid()) {
            $this->validateLeaveBalance(
                $data['employee_id'],
                $data['leave_type_id'],
                $data['total_days'] ?? $this->calculateLeaveDays($data['start_date'], $data['end_date'])
            );
        }
        
        // Set default status
        $data['status'] ??= LeaveStatus::PENDING->value;
        $data['submitted_at'] ??= now();
        
        return $this->leaveRepository->create($data);
    }
    
    /**
     * Approve a leave request.
     *
     * @param string $id Leave ULID
     * @param string $approverId Approver's employee ULID
     * @return LeaveInterface
     * @throws LeaveNotFoundException
     * @throws LeaveValidationException
     */
    public function approveLeave(string $id, string $approverId): LeaveInterface
    {
        $leave = $this->getLeaveById($id);
        
        if (!$leave->isPending()) {
            throw new LeaveValidationException("Only pending leave requests can be approved.");
        }
        
        $updatedLeave = $this->leaveRepository->update($id, [
            'status' => LeaveStatus::APPROVED->value,
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);
        
        // Deduct from leave balance
        $this->deductLeaveBalance(
            $leave->getEmployeeId(),
            $leave->getLeaveTypeId(),
            $leave->getTotalDays()
        );
        
        return $updatedLeave;
    }
    
    /**
     * Reject a leave request.
     *
     * @param string $id Leave ULID
     * @param string $approverId Approver's employee ULID
     * @param string $reason Rejection reason
     * @return LeaveInterface
     * @throws LeaveNotFoundException
     * @throws LeaveValidationException
     */
    public function rejectLeave(string $id, string $approverId, string $reason): LeaveInterface
    {
        $leave = $this->getLeaveById($id);
        
        if (!$leave->isPending()) {
            throw new LeaveValidationException("Only pending leave requests can be rejected.");
        }
        
        return $this->leaveRepository->update($id, [
            'status' => LeaveStatus::REJECTED->value,
            'approved_by' => $approverId,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
    
    /**
     * Cancel a leave request.
     *
     * @param string $id Leave ULID
     * @param string $reason Cancellation reason
     * @return LeaveInterface
     * @throws LeaveNotFoundException
     * @throws LeaveValidationException
     */
    public function cancelLeave(string $id, string $reason): LeaveInterface
    {
        $leave = $this->getLeaveById($id);
        
        if ($leave->isCancelled()) {
            throw new LeaveValidationException("Leave request is already cancelled.");
        }
        
        // Refund leave balance if already approved
        if ($leave->isApproved()) {
            $this->refundLeaveBalance(
                $leave->getEmployeeId(),
                $leave->getLeaveTypeId(),
                $leave->getTotalDays()
            );
        }
        
        return $this->leaveRepository->update($id, [
            'status' => LeaveStatus::CANCELLED->value,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }
    
    /**
     * Get leave request by ID.
     *
     * @param string $id Leave ULID
     * @return LeaveInterface
     * @throws LeaveNotFoundException
     */
    public function getLeaveById(string $id): LeaveInterface
    {
        $leave = $this->leaveRepository->findById($id);
        
        if (!$leave) {
            throw LeaveNotFoundException::forId($id);
        }
        
        return $leave;
    }
    
    /**
     * Get leave balance for employee and leave type.
     *
     * @param string $employeeId Employee ULID
     * @param string $leaveTypeId Leave type ULID
     * @param int|null $year Year (defaults to current year)
     * @return float Remaining balance
     */
    public function getLeaveBalance(string $employeeId, string $leaveTypeId, ?int $year = null): float
    {
        $year ??= (int) date('Y');
        
        $balance = $this->leaveBalanceRepository->getBalance($employeeId, $leaveTypeId, $year);
        
        return $balance ? $balance->getRemainingDays() : 0.0;
    }
    
    /**
     * Initialize leave balance for employee.
     *
     * @param string $employeeId Employee ULID
     * @param string $leaveTypeId Leave type ULID
     * @param int $year Year
     * @param float $entitledDays Entitled days
     * @return void
     */
    public function initializeLeaveBalance(
        string $employeeId,
        string $leaveTypeId,
        int $year,
        float $entitledDays
    ): void {
        $this->leaveBalanceRepository->createOrUpdate([
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'year' => $year,
            'entitled_days' => $entitledDays,
            'used_days' => 0.0,
            'carried_forward_days' => 0.0,
        ]);
    }
    
    /**
     * Calculate leave days between two dates.
     *
     * @param string|DateTimeInterface $startDate
     * @param string|DateTimeInterface $endDate
     * @return float Number of days
     */
    private function calculateLeaveDays($startDate, $endDate): float
    {
        if (is_string($startDate)) {
            $startDate = new \DateTime($startDate);
        }
        if (is_string($endDate)) {
            $endDate = new \DateTime($endDate);
        }
        
        $diff = $startDate->diff($endDate);
        return (float) ($diff->days + 1); // Include both start and end dates
    }
    
    /**
     * Validate leave data.
     *
     * @param array<string, mixed> $data
     * @throws LeaveValidationException
     */
    private function validateLeaveData(array $data): void
    {
        if (!isset($data['start_date']) || !isset($data['end_date'])) {
            throw LeaveValidationException::missingRequiredField('start_date or end_date');
        }
        
        $startDate = new \DateTime($data['start_date']);
        $endDate = new \DateTime($data['end_date']);
        
        if ($endDate < $startDate) {
            throw LeaveValidationException::endDateBeforeStartDate();
        }
    }
    
    /**
     * Validate leave balance is sufficient.
     *
     * @param string $employeeId Employee ULID
     * @param string $leaveTypeId Leave type ULID
     * @param float $requestedDays Requested days
     * @throws LeaveValidationException
     */
    private function validateLeaveBalance(string $employeeId, string $leaveTypeId, float $requestedDays): void
    {
        $availableDays = $this->getLeaveBalance($employeeId, $leaveTypeId);
        
        if ($requestedDays > $availableDays) {
            throw LeaveValidationException::insufficientBalance($requestedDays, $availableDays);
        }
    }
    
    /**
     * Deduct leave balance.
     *
     * @param string $employeeId Employee ULID
     * @param string $leaveTypeId Leave type ULID
     * @param float $days Days to deduct
     */
    private function deductLeaveBalance(string $employeeId, string $leaveTypeId, float $days): void
    {
        $year = (int) date('Y');
        
        $this->leaveBalanceRepository->adjustBalance($employeeId, $leaveTypeId, $year, -$days);
    }
    
    /**
     * Refund leave balance.
     *
     * @param string $employeeId Employee ULID
     * @param string $leaveTypeId Leave type ULID
     * @param float $days Days to refund
     */
    private function refundLeaveBalance(string $employeeId, string $leaveTypeId, float $days): void
    {
        $year = (int) date('Y');
        
        $this->leaveBalanceRepository->adjustBalance($employeeId, $leaveTypeId, $year, $days);
    }
}
