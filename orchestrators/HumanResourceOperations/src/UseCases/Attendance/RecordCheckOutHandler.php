<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Attendance;

use Nexus\HumanResourceOperations\Coordinators\AttendanceCoordinator;
use Nexus\HumanResourceOperations\DTOs\AttendanceCheckRequest;
use Nexus\HumanResourceOperations\DTOs\AttendanceCheckResult;

final readonly class RecordCheckOutHandler
{
    public function __construct(
        private AttendanceCoordinator $attendanceCoordinator
    ) {}

    public function handle(
        string $employeeId,
        \DateTimeImmutable $timestamp,
        ?array $metadata = null
    ): AttendanceCheckResult {
        $request = new AttendanceCheckRequest(
            employeeId: $employeeId,
            timestamp: $timestamp,
            type: 'check_out',
            metadata: $metadata,
        );

        return $this->attendanceCoordinator->processAttendanceCheck($request);
    }
}
