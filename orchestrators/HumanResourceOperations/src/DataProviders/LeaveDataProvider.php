<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\DataProviders;

use Nexus\Leave\Contracts\LeaveBalanceRepositoryInterface;
use Nexus\Leave\Contracts\LeaveRepositoryInterface;
use Nexus\Leave\Contracts\LeaveTypeRepositoryInterface;

use Nexus\HumanResourceOperations\DTOs\LeaveContext;

/**
 * Data Provider for leave operations.
 */
final readonly class LeaveDataProvider
{
    public function __construct(
        private ?LeaveRepositoryInterface $leaveRepository = null,
        private ?LeaveBalanceRepositoryInterface $balanceRepository = null,
        private ?LeaveTypeRepositoryInterface $leaveTypeRepository = null,
    ) {}

    public function getLeaveContext(
        string $employeeId,
        string $leaveTypeId,
        string $startDate,
        string $endDate,
        float $daysRequested,
        ?string $applicantUserId = null,
        ?string $applicantName = null,
    ): LeaveContext {
        $leaveType = $this->leaveTypeRepository?->findById($leaveTypeId);
        $balance = $this->getCurrentBalance($employeeId, $leaveTypeId);

        return new LeaveContext(
            employeeId: $employeeId,
            employeeName: 'Employee ' . $employeeId,
            departmentId: 'unknown',
            leaveTypeId: $leaveTypeId,
            leaveTypeName: $this->readString($leaveType, ['getName', 'name']) ?? 'Unknown Leave Type',
            currentBalance: $balance,
            daysRequested: $daysRequested,
            startDate: $startDate,
            endDate: $endDate,
            applicantUserId: $applicantUserId ?? $employeeId,
            applicantName: $applicantName ?? ('Applicant ' . $employeeId),
            isProxyApplication: ($applicantUserId ?? $employeeId) !== $employeeId,
            policyRules: $this->getPolicyRules($leaveTypeId),
            existingLeaves: $this->leaveRepository?->findByEmployeeId($employeeId) ?? [],
        );
    }

    public function getCurrentBalance(string $employeeId, string $leaveTypeId): float
    {
        $balance = $this->balanceRepository?->findByEmployeeAndType($employeeId, $leaveTypeId);

        if ($balance === null) {
            return 0.0;
        }

        return (float) ($this->readString($balance, ['getBalance']) ?? $balance->balance ?? 0.0);
    }

    public function hasOverlappingLeaves(string $employeeId, string $startDate, string $endDate): bool
    {
        $requestedStart = new \DateTimeImmutable($startDate);
        $requestedEnd = new \DateTimeImmutable($endDate);

        foreach ($this->leaveRepository?->findByEmployeeId($employeeId) ?? [] as $leave) {
            $status = $this->readString($leave, ['getStatus', 'status']);
            if ($status === null || strtolower($status) !== 'approved') {
                continue;
            }

            $leaveStart = $this->asDate($this->readString($leave, ['getStartDate', 'startDate', 'start_date']));
            $leaveEnd = $this->asDate($this->readString($leave, ['getEndDate', 'endDate', 'end_date']));

            if ($leaveStart === null || $leaveEnd === null) {
                continue;
            }

            if ($leaveStart <= $requestedEnd && $leaveEnd >= $requestedStart) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string,mixed> */
    public function getPolicyRules(string $leaveTypeId): array
    {
        $leaveType = $this->leaveTypeRepository?->findById($leaveTypeId);

        return [
            'max_consecutive_days' => $this->readString($leaveType, ['getMaxConsecutiveDays', 'maxConsecutiveDays']) ?? null,
            'requires_documentation' => $this->readString($leaveType, ['getRequiresDocumentation', 'requiresDocumentation']) ?? false,
        ];
    }

    /** @param array<string> $candidates */
    private function readString(?object $source, array $candidates): mixed
    {
        if ($source === null) {
            return null;
        }

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

        if (is_string($value) && $value !== '') {
            return new \DateTimeImmutable($value);
        }

        return null;
    }
}
