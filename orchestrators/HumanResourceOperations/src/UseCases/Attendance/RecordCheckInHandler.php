<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Attendance;

use Nexus\HumanResourceOperations\Coordinators\AttendanceCoordinator;
use Nexus\HumanResourceOperations\DTOs\AttendanceCheckRequest;
use Nexus\HumanResourceOperations\DTOs\AttendanceCheckResult;

final readonly class RecordCheckInHandler
{
    public function __construct(
        private AttendanceCoordinator $attendanceCoordinator
    ) {}

    public function handle(
        string $employeeId,
        \DateTimeImmutable $timestamp,
        ?string $locationId = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $deviceId = null,
        ?array $metadata = null
    ): AttendanceCheckResult {
        $request = new AttendanceCheckRequest(
            employeeId: $employeeId,
            timestamp: $timestamp,
            type: 'check_in',
            locationId: $locationId,
            latitude: $latitude,
            longitude: $longitude,
            deviceId: $deviceId,
            metadata: $metadata,
        );

        return $this->attendanceCoordinator->processAttendanceCheck($request);
    }
}
