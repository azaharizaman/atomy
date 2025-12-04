<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\DTOs;

/**
 * Request DTO for leave application.
 */
final readonly class LeaveApplicationRequest
{
    public function __construct(
        public string $employeeId,
        public string $leaveTypeId,
        public string $startDate,
        public string $endDate,
        public string $reason,
        public string $requestedBy,
        public ?float $daysRequested = null,
        public ?array $metadata = null,
    ) {}
}
