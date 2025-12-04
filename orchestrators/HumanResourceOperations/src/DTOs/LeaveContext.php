<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\DTOs;

/**
 * Context DTO for leave operations.
 * 
 * Aggregates employee, leave balance, and policy data.
 */
final readonly class LeaveContext
{
    public function __construct(
        public string $employeeId,
        public string $employeeName,
        public string $departmentId,
        public string $leaveTypeId,
        public string $leaveTypeName,
        public float $currentBalance,
        public float $daysRequested,
        public string $startDate,
        public string $endDate,
        public string $applicantUserId,
        public string $applicantName,
        public bool $isProxyApplication,
        public ?array $policyRules = null,
        public ?array $existingLeaves = null,
        public ?array $metadata = null,
    ) {}
}
