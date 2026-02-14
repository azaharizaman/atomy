<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\DataProviders;

use Nexus\Attendance\Contracts\AttendanceQueryInterface;
use Nexus\Common\ValueObjects\Money;
use Nexus\EmployeeProfile\Contracts\EmployeeRepositoryInterface;
use Nexus\HumanResourceOperations\DTOs\PayrollContext;
use Nexus\Leave\Contracts\LeaveRepositoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Aggregates payroll-related data from multiple sources.
 */
final readonly class PayrollDataProvider
{
    public function __construct(
        private ?EmployeeRepositoryInterface $employeeRepository = null,
        private ?AttendanceQueryInterface $attendanceQuery = null,
        private ?LeaveRepositoryInterface $leaveRepository = null,
        private ?\Closure $earningsResolver = null,
        private ?\Closure $deductionsResolver = null,
        private ?\Closure $previousPayslipResolver = null,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function getPayrollContext(
        string $employeeId,
        string $periodId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): PayrollContext {
        $baseSalary = $this->getEmployeeBaseSalary($employeeId);
        $workingHours = $this->calculateWorkingHours($employeeId, $periodStart, $periodEnd);
        $overtimeHours = $this->calculateOvertimeHours($employeeId, $periodStart, $periodEnd);
        $earnings = $this->getEarningsComponents($employeeId, $periodId);
        $deductions = $this->getDeductionsComponents($employeeId, $periodId);
        $attendanceRecords = $this->getAttendanceRecords($employeeId, $periodStart, $periodEnd);
        $leaveRecords = $this->getLeaveRecords($employeeId, $periodStart, $periodEnd);
        $previousPayslip = $this->getPreviousPayslip($employeeId, $periodStart);

        return new PayrollContext(
            employeeId: $employeeId,
            periodId: $periodId,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            baseSalary: $baseSalary,
            totalWorkingHours: $workingHours,
            totalOvertimeHours: $overtimeHours,
            earnings: $earnings,
            deductions: $deductions,
            attendanceRecords: $attendanceRecords,
            leaveRecords: $leaveRecords,
            previousPayslip: $previousPayslip
        );
    }

    private function getEmployeeBaseSalary(string $employeeId): Money
    {
        if ($this->employeeRepository === null) {
            return Money::zero('MYR');
        }

        $employee = $this->employeeRepository->findById($employeeId);
        if ($employee === null) {
            return Money::zero('MYR');
        }

        $salary = $this->readValue($employee, ['getBaseSalary', 'baseSalary', 'salary']);

        if ($salary instanceof Money) {
            return $salary;
        }

        if (is_numeric($salary)) {
            return Money::of((float) $salary, 'MYR');
        }

        return Money::zero('MYR');
    }

    private function calculateWorkingHours(string $employeeId, \DateTimeImmutable $start, \DateTimeImmutable $end): float
    {
        if ($this->attendanceQuery === null) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($this->attendanceQuery->findByEmployeeAndDateRange($employeeId, $start, $end) as $record) {
            $hours = $record->getWorkHours();
            if ($hours !== null) {
                $total += $hours->getTotalHours();
            }
        }

        return $total;
    }

    private function calculateOvertimeHours(string $employeeId, \DateTimeImmutable $start, \DateTimeImmutable $end): float
    {
        if ($this->attendanceQuery === null) {
            return 0.0;
        }

        $overtime = 0.0;
        foreach ($this->attendanceQuery->findByEmployeeAndDateRange($employeeId, $start, $end) as $record) {
            $hours = $record->getWorkHours();
            if ($hours !== null) {
                $overtime += $hours->overtimeHours;
            }
        }

        return $overtime;
    }

    private function getEarningsComponents(string $employeeId, string $periodId): array
    {
        if ($this->earningsResolver === null) {
            return [];
        }

        return ($this->earningsResolver)($employeeId, $periodId);
    }

    private function getDeductionsComponents(string $employeeId, string $periodId): array
    {
        if ($this->deductionsResolver === null) {
            return [];
        }

        return ($this->deductionsResolver)($employeeId, $periodId);
    }

    private function getAttendanceRecords(string $employeeId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        if ($this->attendanceQuery === null) {
            return [];
        }

        $records = $this->attendanceQuery->findByEmployeeAndDateRange($employeeId, $start, $end);

        return array_map(static fn ($record): array => [
            'id' => $record->getId()->toString(),
            'date' => $record->getDate()->format('Y-m-d'),
            'check_in' => $record->getCheckInTime()?->format('Y-m-d H:i:s'),
            'check_out' => $record->getCheckOutTime()?->format('Y-m-d H:i:s'),
        ], $records);
    }

    private function getLeaveRecords(string $employeeId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        if ($this->leaveRepository === null) {
            return [];
        }

        $leaves = $this->leaveRepository->findByEmployeeId($employeeId);

        return array_values(array_filter($leaves, function ($leave) use ($start, $end): bool {
            $leaveStart = $this->asDate($this->readValue($leave, ['getStartDate', 'startDate']));
            $leaveEnd = $this->asDate($this->readValue($leave, ['getEndDate', 'endDate']));

            if ($leaveStart === null || $leaveEnd === null) {
                return false;
            }

            return $leaveStart <= $end && $leaveEnd >= $start;
        }));
    }

    private function getPreviousPayslip(string $employeeId, \DateTimeImmutable $beforeDate): ?array
    {
        if ($this->previousPayslipResolver === null) {
            return null;
        }

        return ($this->previousPayslipResolver)($employeeId, $beforeDate);
    }

    /** @param array<string> $candidates */
    private function readValue(object $source, array $candidates): mixed
    {
        foreach ($candidates as $candidate) {
            if (method_exists($source, $candidate)) {
                return $source->{$candidate}();
            }

            if (property_exists($source, $candidate)) {
                return $source->{$candidate};
            }
        }

        return null;
    }

    private function asDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if (is_string($value)) {
            return new \DateTimeImmutable($value);
        }

        return null;
    }
}
