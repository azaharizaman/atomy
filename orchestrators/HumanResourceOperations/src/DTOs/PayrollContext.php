<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\DTOs;

use Nexus\Common\ValueObjects\Money;

/**
 * Context DTO aggregating data needed for payroll calculation and validation
 */
final readonly class PayrollContext
{
    public function __construct(
        public string $employeeId,
        public string $periodId,
        public \DateTimeImmutable $periodStart,
        public \DateTimeImmutable $periodEnd,
        public Money $baseSalary,
        public float $totalWorkingHours,
        public float $totalOvertimeHours,
        public array $earnings,
        public array $deductions,
        public array $attendanceRecords,
        public array $leaveRecords,
        public ?array $previousPayslip = null,
        public ?array $metadata = null
    ) {}
}
